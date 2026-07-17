{{--
    Shared shell for HTTP error pages. Deliberately minimal: no Livewire,
    header, or footer, so these render even when the application itself is
    broken (500) or down for maintenance (503).
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="robots" content="noindex" />

    <title>@yield('title') - {{ config('app.name') }}</title>

    <link rel="icon" href="/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    @fonts

    @vite('resources/css/public.css')
</head>

<body class="min-h-screen bg-cream font-sans text-gray-900 antialiased">
    <div class="flex min-h-screen flex-col items-center justify-center gap-10 px-4 py-16 text-center">
        <a href="{{ route('home') }}">
            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="h-24 w-auto">
        </a>

        <div class="flex max-w-md flex-col gap-3">
            <p class="font-heading text-lg font-bold text-brand">@yield('code')</p>

            <h1 class="font-heading text-3xl font-bold tracking-[-0.04em] text-gray-900 sm:text-4xl">
                @yield('heading')
            </h1>

            <p class="text-base leading-7 text-gray-700">
                @yield('message')
            </p>
        </div>

        <div class="flex flex-wrap items-center justify-center gap-3">
            @yield('actions')
        </div>
    </div>
</body>

</html>
