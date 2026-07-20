{{-- Tailwind v4 + Alpine from CDN – no Vite, no build step anywhere in this
     product. Design tokens come straight from resources/css/app.css, inlined
     at runtime minus the build-only @import/@source lines the in-browser
     compiler does not use. Reading the file (rather than pasting CSS here)
     keeps @theme/@apply out of the Blade source, so Blade never mistakes them
     for directives. The token assembly lives in the component class. --}}
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>
<style type="text/tailwindcss">{!! $tokens !!}</style>
<link rel="stylesheet" href="{{ asset_v('css/municipal.css') }}">
{{-- Alpine powers dropdowns, toggles, modals, tabs. Focus plugin loads first. --}}
<script defer src="https://cdn.jsdelivr.net/npm/@alpinejs/focus@3/dist/cdn.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3/dist/cdn.min.js"></script>
<script defer src="{{ asset_v('js/municipal.js') }}"></script>
{{-- Search Appearance counters and preview. Plain DOM, no Alpine.data(), so
     its position relative to the Alpine bundle above does not matter. --}}
<script defer src="{{ asset_v('js/seo.js') }}"></script>
