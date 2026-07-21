<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChangelogEntry;
use Illuminate\Http\Request;

class ChangelogEntryController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.changelog.index', [
            'entries' => ChangelogEntry::when($request->filled('q'), fn ($q) => $q->where(function ($w) use ($request) {
                $like = '%'.$request->string('q').'%';
                $w->where('title', 'like', $like)->orWhere('version', 'like', $like);
            }))
                ->timeline()
                ->paginate((int) config('shop.rows_per_page', 25))
                ->withQueryString(),
            'filters' => $request->only('q'),
        ]);
    }

    public function create()
    {
        return view('admin.changelog.create', [
            'entry' => new ChangelogEntry([
                'is_published' => true,
                'released_on' => now()->toDateString(),
            ]),
        ]);
    }

    public function store(Request $request)
    {
        $entry = ChangelogEntry::create($this->validated($request));

        return redirect()->route('changelog.edit', $entry)->with('status', 'Release note created.');
    }

    public function edit(ChangelogEntry $changelog)
    {
        return view('admin.changelog.edit', [
            'entry' => $changelog,
        ]);
    }

    public function update(Request $request, ChangelogEntry $changelog)
    {
        $changelog->update($this->validated($request));

        return back()->with('status', 'Release note saved.');
    }

    public function destroy(ChangelogEntry $changelog)
    {
        $changelog->delete();

        return redirect()->route('changelog.index')->with('status', 'Release note deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $ids = collect($request->input('ids', []))->filter()->all();
        $count = 0;
        foreach (ChangelogEntry::whereIn('id', $ids)->get() as $entry) {
            $entry->delete();
            $count++;
        }

        return back()->with('status', "Deleted {$count} release note(s).");
    }

    private function validated(Request $request): array
    {
        $validated = $request->validate([
            'version' => ['required', 'string', 'max:50'],
            'released_on' => ['required', 'date'],
            'title' => ['required', 'string', 'max:255'],
            'summary' => ['nullable', 'string', 'max:500'],
            'body' => ['nullable', 'string'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        return [
            'version' => $validated['version'],
            'released_on' => $validated['released_on'],
            'title' => $validated['title'],
            'summary' => $validated['summary'] ?? null,
            'body' => $validated['body'] ?? null,
            'position' => (int) ($validated['position'] ?? 0),
            'is_published' => $request->boolean('is_published'),
        ];
    }
}
