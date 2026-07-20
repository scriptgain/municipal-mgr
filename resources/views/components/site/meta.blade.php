{{-- Public site <head> meta. Markup only: every value is resolved by
     App\View\Components\Site\Meta via the Seo service. Nothing here decides
     anything, which is why no page can drift out of sync. --}}
<title>{{ $meta['title'] }}</title>
@if ($meta['description'])
    <meta name="description" content="{{ $meta['description'] }}">
@endif
<meta name="robots" content="{{ $meta['robots'] }}">
<link rel="canonical" href="{{ $meta['canonical'] }}">

@foreach ($meta['og'] as $property => $content)
    <meta property="{{ $property }}" content="{{ $content }}">
@endforeach

@foreach ($meta['twitter'] as $name => $content)
    <meta name="{{ $name }}" content="{{ $content }}">
@endforeach

@foreach ($meta['verification'] as $name => $content)
    <meta name="{{ $name }}" content="{{ $content }}">
@endforeach

@if ($meta['json_ld'])
    <script type="application/ld+json">{!! $meta['json_ld'] !!}</script>
@endif
