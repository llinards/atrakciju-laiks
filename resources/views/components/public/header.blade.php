@php
    $attractionItems = [
        ['label' => 'Visas piepūšamās atrakcijas', 'href' => '#'],
    ];

    $navigationItems = [
        ['label' => 'Teltis', 'href' => '#'],
        ['label' => 'Nojumes', 'href' => '#'],
        ['label' => 'Pārdošanas sadaļa', 'href' => '#'],
        ['label' => 'Galerija', 'href' => '#'],
        ['label' => 'Kontakti', 'href' => '#'],
    ];
@endphp

<header
    {{ $attributes->merge(['class' => 'border-b border-gray-100 bg-white shadow-[0px_1px_1px_0px_rgba(0,0,0,0.25)]']) }}
    x-data="{ mobileMenuOpen: false }"
>
    <div class="flex min-h-[34px] items-center justify-center bg-brand px-4 py-1 text-center">
        <p class="text-sm font-semibold text-white">
            Mēs piegādāsim atrakciju Jums ērtā vietā un laikā visā Latvijā |
            <a href="tel:{{ str_replace(' ', '', config('site.phone')) }}" class="underline underline-offset-2 hover:text-gray-100">{{ config('site.phone') }}</a>
        </p>
    </div>

    <nav class="mx-auto flex h-20 max-w-7xl items-center justify-between gap-4 px-4 lg:px-8">
        <a href="{{ route('home') }}" wire:navigate class="shrink-0">
            <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="h-14 w-auto">
        </a>

        <div class="hidden items-center gap-1 lg:flex">
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 hover:text-gray-900"
                    @click="open = !open"
                    :aria-expanded="open"
                >
                    Piepūšamās atrakcijas
                    <x-public.icons.chevron-down class="size-5 transition-transform duration-200" ::class="open && 'rotate-180'" />
                </button>

                <div
                    x-cloak
                    x-show="open"
                    x-transition.origin.top
                    class="absolute left-0 top-full z-20 mt-2 min-w-56 rounded-xl border border-gray-100 bg-white py-2 shadow-lg"
                >
                    @foreach ($attractionItems as $item)
                        <a href="{{ $item['href'] }}" class="block px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            @foreach ($navigationItems as $item)
                <a href="{{ $item['href'] }}" class="rounded-xl px-3.5 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>

        <button
            type="button"
            class="rounded-xl p-2 text-gray-600 hover:bg-gray-50 lg:hidden"
            @click="mobileMenuOpen = !mobileMenuOpen"
            :aria-expanded="mobileMenuOpen"
            aria-label="Izvēlne"
        >
            <svg class="size-6" x-show="!mobileMenuOpen" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
            <svg class="size-6" x-cloak x-show="mobileMenuOpen" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </nav>

    <div x-cloak x-show="mobileMenuOpen" x-transition.origin.top class="border-t border-gray-100 px-4 pb-4 pt-2 lg:hidden">
        <a href="#" class="block rounded-xl px-3.5 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 hover:text-gray-900">
            Piepūšamās atrakcijas
        </a>
        @foreach ($navigationItems as $item)
            <a href="{{ $item['href'] }}" class="block rounded-xl px-3.5 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</header>
