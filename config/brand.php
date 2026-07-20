<?php

// Branding. Rename the whole product from one place. These defaults can be
// overridden by env, and by DB settings applied at boot (the Branding settings
// screen) — matching the DB-driven config pattern.
return [
    'name' => env('BRAND_NAME', env('APP_NAME', 'MunicipalMGR')),
    'tagline' => env('BRAND_TAGLINE', 'Municipal Website Platform'),
    // Accent hex; overrides the civic-navy brand ramp at runtime. Settable in the UI.
    // Navy keeps rose/amber free for alert + emergency states, which matter more
    // on a government site than on an internal panel.
    'accent' => env('BRAND_ACCENT', '#0f4c81'),
    // Secondary civic accent (seal gold). Used for rules, eyebrows, and the
    // public site's ceremonial trim.
    'accent_alt' => env('BRAND_ACCENT_ALT', '#c8a45c'),
    // Logo/favicon glyph (an x-icon name). Distinct per product.
    'icon' => env('BRAND_ICON', 'building'),
];
