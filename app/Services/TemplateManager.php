<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\TemplateOverride;
use App\Models\TemplateOverrideVersion;
use Illuminate\Support\Facades\View;

/**
 * Everything the Template Manager does to a template, in one place, so the
 * controller stays a controller and every mutation gets the same treatment:
 * validate first, persist second, write a version, write the audit log.
 *
 * There is exactly one rule here that matters: nothing reaches
 * template_overrides.source without passing TemplateValidator. Publish, revert
 * and preview all funnel through the same gate, because a revert to a version
 * saved before a validator improvement is still merchant code being put in
 * front of customers.
 */
class TemplateManager
{
    public function __construct(
        private TemplateValidator $validator,
        private TemplateOverrideResolver $resolver,
    ) {}

    /* ------------------------------------------------------------------ *
     * Catalogue
     * ------------------------------------------------------------------ */

    /**
     * The editable template catalogue, decorated with live state.
     *
     * Returns groups of rows the index view can render directly, because a
     * view must not compute this itself.
     */
    public function catalogue(): array
    {
        $overridden = array_flip($this->resolver->overriddenViews());
        $groups = [];

        foreach ((array) config('templates.groups', []) as $key => $group) {
            $rows = [];

            foreach ((array) ($group['views'] ?? []) as $view => $meta) {
                [$label, $description, $risk] = array_pad((array) $meta, 3, 'normal');

                $rows[] = [
                    'view' => $view,
                    'label' => $label,
                    'description' => $description,
                    'risk' => $risk,
                    'overridden' => isset($overridden[$view]),
                    'exists' => $this->shippedPath($view) !== null,
                ];
            }

            $groups[$key] = [
                'key' => $key,
                'label' => $group['label'] ?? ucfirst($key),
                'icon' => $group['icon'] ?? 'edit',
                'description' => $group['description'] ?? '',
                'rows' => $rows,
                'overridden_count' => count(array_filter($rows, fn ($r) => $r['overridden'])),
            ];
        }

        return $groups;
    }

    /** Metadata for one editable view, or null when it is not in the catalogue. */
    public function meta(string $view): ?array
    {
        foreach ((array) config('templates.groups', []) as $key => $group) {
            if (isset($group['views'][$view])) {
                [$label, $description, $risk] = array_pad((array) $group['views'][$view], 3, 'normal');

                return [
                    'view' => $view,
                    'label' => $label,
                    'description' => $description,
                    'risk' => $risk,
                    'group' => $group['label'] ?? ucfirst($key),
                    'group_key' => $key,
                ];
            }
        }

        return null;
    }

    public function isEditable(string $view): bool
    {
        return $this->meta($view) !== null;
    }

    /* ------------------------------------------------------------------ *
     * Reading
     * ------------------------------------------------------------------ */

    /** The template as ShopMGR ships it, straight off the release's filesystem. */
    public function shippedSource(string $view): string
    {
        $path = $this->shippedPath($view);

        return $path ? (string) @file_get_contents($path) : '';
    }

    /**
     * The shipped file's path, resolved against the real view paths rather than
     * through the finder (the finder is exactly what we are bypassing here).
     */
    public function shippedPath(string $view): ?string
    {
        $relative = str_replace('.', '/', $view);

        foreach (View::getFinder()->getPaths() as $base) {
            foreach (['.blade.php', '.php'] as $extension) {
                $candidate = rtrim($base, '/').'/'.$relative.$extension;

                if (is_file($candidate)) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    public function override(string $view): ?TemplateOverride
    {
        return TemplateOverride::where('view', $view)->first();
    }

    /** What is live right now: the override if there is one, else the shipped file. */
    public function currentSource(string $view): string
    {
        return $this->override($view)?->source ?? $this->shippedSource($view);
    }

    public function versions(string $view)
    {
        return TemplateOverrideVersion::with('user')
            ->where('view', $view)
            ->orderByDesc('id')
            ->limit(100)
            ->get();
    }

    /* ------------------------------------------------------------------ *
     * Writing
     * ------------------------------------------------------------------ */

    /**
     * Publish an edit.
     *
     * @return array{ok: bool, error: ?string, line: ?int, excerpt: ?array, exact: bool}
     */
    public function publish(string $view, string $source, ?string $note = null, string $action = 'save'): array
    {
        $result = $this->validator->validate($source);

        if (! $result['ok']) {
            return $result;
        }

        $source = $this->normalise($source);

        $override = TemplateOverride::updateOrCreate(
            ['view' => $view],
            ['source' => $source, 'updated_by' => auth()->id()]
        );

        TemplateOverrideVersion::create([
            'template_override_id' => $override->id,
            'view' => $view,
            'source' => $source,
            'action' => $action,
            'note' => $note,
            'user_id' => auth()->id(),
        ]);

        $this->resolver->forget();
        $this->resolver->purge($view);

        AuditLog::record(
            'template.'.$action,
            ucfirst($action).' template override for "'.$view.'"'.($note ? ' ('.$note.')' : ''),
            $override
        );

        return $result;
    }

    /**
     * Roll back to a stored version.
     *
     * History is append-only: this writes the old source forward as a new
     * version rather than deleting anything, so a revert is itself revertable.
     */
    public function revert(string $view, TemplateOverrideVersion $version): array
    {
        if ($version->view !== $view || $version->source === null) {
            return ['ok' => false, 'error' => 'That version does not belong to this template.', 'line' => null, 'excerpt' => null, 'exact' => true];
        }

        return $this->publish($view, $version->source, 'Reverted to version #'.$version->id, 'revert');
    }

    /**
     * Drop the override entirely and go back to the shipped template.
     *
     * The version history survives on purpose (the versions table keeps the
     * view name and nulls its parent), so a reset is not a shredder.
     */
    public function reset(string $view): void
    {
        $override = $this->override($view);

        if (! $override) {
            return;
        }

        TemplateOverrideVersion::create([
            'template_override_id' => $override->id,
            'view' => $view,
            'source' => null,
            'action' => 'reset',
            'note' => 'Reset to shipped default',
            'user_id' => auth()->id(),
        ]);

        AuditLog::record('template.reset', 'Reset template "'.$view.'" to the shipped default', $override);

        $override->delete();

        $this->resolver->purge($view);
        $this->resolver->forget();
    }

    /* ------------------------------------------------------------------ *
     * Preview
     * ------------------------------------------------------------------ */

    /**
     * Stage an unpublished draft for this session only.
     *
     * Validated with exactly the same gate as a publish: a preview that can 500
     * the admin's own browsing session is not a preview, it is a smaller
     * outage.
     */
    public function preview(string $view, string $source): array
    {
        $result = $this->validator->validate($source);

        if (! $result['ok']) {
            return $result;
        }

        // A draft lives in the session. On the cookie session driver that means
        // it lives in a 4KB cookie, so a real template would be silently
        // truncated and the merchant would preview something that is not what
        // they typed. Say so instead.
        if (config('session.driver') === 'cookie' && strlen($source) > 3000) {
            return [
                'ok' => false,
                'error' => 'This template is too large to preview while the session driver is set to "cookie". '
                    .'Switch the session driver to "database" or "file", or save the template instead.',
                'line' => null,
                'excerpt' => null,
                'exact' => true,
            ];
        }

        $drafts = session('template_preview', []);
        $drafts = is_array($drafts) ? $drafts : [];
        $drafts[$view] = ['source' => $this->normalise($source), 'at' => time()];

        session(['template_preview' => $drafts]);

        return $result;
    }

    public function stopPreview(?string $view = null): void
    {
        if ($view === null) {
            session()->forget('template_preview');

            return;
        }

        $drafts = session('template_preview', []);
        unset($drafts[$view]);
        session(['template_preview' => $drafts]);
    }

    /** Views currently being previewed in this session. */
    public function previewing(): array
    {
        return array_keys($this->resolver->previewDrafts());
    }

    /* ------------------------------------------------------------------ *
     * Helpers
     * ------------------------------------------------------------------ */

    /** Normalise line endings so diffs are about content, not about Windows. */
    private function normalise(string $source): string
    {
        return str_replace(["\r\n", "\r"], "\n", $source);
    }
}
