{{-- Re-tint the brand ramp from a single accent, after the browser Tailwind
     build has injected its own styles. Which accent wins (an active theme vs
     the Branding settings screen) is decided in App\View\Components\AccentStyle
     so this file stays markup only. Note there is deliberately no @php on line
     one: Blade mis-parses a raw PHP block in the first line of a view. --}}
@if ($needed)
    <style>
        :root {
            --accent: {{ $accent }};
            --color-brand-50: color-mix(in srgb, var(--accent), white 92%);
            --color-brand-100: color-mix(in srgb, var(--accent), white 85%);
            --color-brand-200: color-mix(in srgb, var(--accent), white 72%);
            --color-brand-300: color-mix(in srgb, var(--accent), white 55%);
            --color-brand-400: color-mix(in srgb, var(--accent), white 30%);
            --color-brand-500: var(--accent);
            --color-brand-600: color-mix(in srgb, var(--accent), black 12%);
            --color-brand-700: color-mix(in srgb, var(--accent), black 25%);
            --color-brand-800: color-mix(in srgb, var(--accent), black 40%);
            --color-brand-900: color-mix(in srgb, var(--accent), black 52%);
            --color-brand-950: color-mix(in srgb, var(--accent), black 68%);
        }
    </style>
@endif
