<?php

/*
|--------------------------------------------------------------------------
| Theme Manager
|--------------------------------------------------------------------------
| The shipped token defaults and the presets that ship with the product.
|
| "defaults" MUST mirror resources/css/app.css exactly. ThemeService compares
| the active theme against these and emits CSS only for tokens that actually
| differ, so an install sitting on the shipped default theme renders byte for
| byte what it rendered before the Theme Manager existed.
*/

return [

    // Shipped look: Civic Navy + seal gold. Mirrors resources/css/app.css.
    'defaults' => [
        'brand' => '#0f4c81',
        'accent' => '#c8a45c',
        'chrome' => '#061f35',
        'chrome_soft' => '#0a2b47',
        'font_sans' => "ui-sans-serif, system-ui, -apple-system, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, 'Noto Sans', sans-serif",
        'font_display' => "'Iowan Old Style', 'Palatino Linotype', Palatino, Georgia, ui-serif, serif",
        // Root font size multiplier. 1 = 16px base.
        'font_scale' => '1',
        // Multiplier on Tailwind's --spacing base (0.25rem).
        'spacing' => '1',
        // Multiplier on the radius ramp. 0 gives square corners.
        'radius' => '1',
        // Chrome treatment for the dark utility bar and public footer.
        'chrome_treatment' => 'dark',
        'logo_url' => '',
        'favicon_url' => '',
    ],

    /*
    | Presets. Three deliberately different civic looks so the feature
    | demonstrates itself the moment an operator opens the screen. Civic Navy is
    | the shipped default and is activated on install.
    */
    'presets' => [
        [
            'name' => 'Civic Navy',
            'slug' => 'civic-navy',
            'description' => 'The shipped look. Deep navy authority with a seal-gold ceremonial accent.',
            'tokens' => [],
        ],
        [
            'name' => 'Heritage Green',
            'slug' => 'heritage-green',
            'description' => 'Parks-and-recreation warmth. Forest green with an aged-brass accent and softer corners.',
            'tokens' => [
                'brand' => '#1f5d3f',
                'accent' => '#b9892f',
                'chrome' => '#0f2f20',
                'chrome_soft' => '#17442f',
                'radius' => '1.5',
            ],
        ],
        [
            'name' => 'Desert Sandstone',
            'slug' => 'desert-sandstone',
            'description' => 'Southwestern civic. Canyon clay with a turquoise accent, a serif display face, and a roomier rhythm.',
            'tokens' => [
                'brand' => '#9a4b2c',
                'accent' => '#2f8f8a',
                'chrome' => '#2b1710',
                'chrome_soft' => '#3d2118',
                'font_display' => "Georgia, 'Times New Roman', ui-serif, serif",
                'spacing' => '1.15',
                'radius' => '0.5',
            ],
        ],
    ],
];
