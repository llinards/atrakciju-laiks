@php($seo = app(App\Support\Seo::class))

<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1.0" />

<title>
    {{ filled($title ?? null) ? $title.' - '.config('app.name', 'Laravel') : config('app.name', 'Laravel') }}
</title>

<meta name="description" content="{{ $seo->description() }}" />
<link rel="canonical" href="{{ $seo->canonicalUrl() }}" />

<meta property="og:site_name" content="{{ config('app.name') }}" />
<meta property="og:locale" content="lv_LV" />
<meta property="og:type" content="{{ $seo->ogType() }}" />
<meta property="og:title" content="{{ filled($title ?? null) ? $title : config('app.name') }}" />
<meta property="og:description" content="{{ $seo->description() }}" />
<meta property="og:url" content="{{ $seo->canonicalUrl() }}" />
<meta property="og:image" content="{{ $seo->imageUrl() }}" />

<meta name="twitter:card" content="summary_large_image" />
<meta name="twitter:title" content="{{ filled($title ?? null) ? $title : config('app.name') }}" />
<meta name="twitter:description" content="{{ $seo->description() }}" />
<meta name="twitter:image" content="{{ $seo->imageUrl() }}" />

@foreach ($seo->jsonLdGraphs() as $graph)
    <script type="application/ld+json">{!! json_encode($graph, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) !!}</script>
@endforeach

<link rel="icon" href="/favicon.ico" sizes="any">
<link rel="icon" href="/favicon.svg" type="image/svg+xml">
<link rel="apple-touch-icon" href="/apple-touch-icon.png">

@fonts

@vite(['resources/css/public.css', 'resources/js/public.js'])
@livewireStyles
