<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.public-head')
</head>

<body class="min-h-screen bg-cream font-sans text-gray-900 antialiased">
    <x-public.header />

    <main>
        {{ $slot }}
    </main>

    <x-public.footer />
    <x-public.floating-contacts />
    <x-public.scroll-top />
    <x-public.reserve-modal />

    @livewireScripts
</body>

</html>
