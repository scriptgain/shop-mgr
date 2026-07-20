<?php

namespace App\View\Components;

use App\Services\ThemeService;
use Illuminate\View\Component;

/**
 * Plain custom properties for the active theme, emitted as a normal <style>.
 *
 * The in-browser Tailwind build injects its stylesheet asynchronously. Without
 * this block there is a window where --color-brand-* is whatever app.css said,
 * which shows up as a colour flash on first paint. Declaring the same values as
 * ordinary CSS closes it.
 */
class AccentStyle extends Component
{
    public string $css;

    public function __construct(ThemeService $themes)
    {
        $theme = $themes->active();

        if ($theme) {
            $this->css = $themes->rootVars($theme);

            return;
        }

        // No theme rows: fall back to the legacy accent behaviour.
        $accent = (string) config('brand.accent');

        if (! $accent || strtolower($accent) === '#e11d48') {
            $this->css = '';

            return;
        }

        $lines = ['--accent: '.$themes->color($accent, '#e11d48').';'];

        foreach ($themes->ramp(['accent' => $accent]) as $step => $value) {
            $lines[] = "--color-brand-{$step}: {$value};";
        }

        $this->css = ':root{'.implode('', $lines).'}';
    }

    public function render()
    {
        return view('components.accent-style');
    }
}
