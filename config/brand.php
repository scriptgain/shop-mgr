<?php

// Branding. Rename the whole product from one place. These defaults can be
// overridden by env, and by DB settings applied at boot (the Branding settings
// screen) — matching the DB-driven config pattern.
return [
    'name' => env('BRAND_NAME', env('APP_NAME', 'ShopMGR')),
    'tagline' => env('BRAND_TAGLINE', 'Self-Hosted Commerce'),
    // Accent hex; overrides the violet brand ramp at runtime. Settable in the UI.
    // Violet is distinct from the rest of the -MGR fleet (cyan/amber/emerald/
    // sky/indigo/bronze) and keeps rose free for destructive + failure states.
    'accent' => env('BRAND_ACCENT', '#e11d48'),
    // Logo/favicon glyph (an x-icon name). Distinct per product.
    'icon' => env('BRAND_ICON', 'bag'),
];
