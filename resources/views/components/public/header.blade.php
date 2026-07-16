@php
    // Category slugs are seeded/managed in admin — keep these in sync with the categories table.
    $attractionItems = array_map(fn (\App\Enums\ProductSize $size): array => [
        'label' => $size->label().' atrakcijas',
        'href' => route('category.show', ['category' => 'piepusamas-atrakcijas', 'size' => $size->value]),
    ], \App\Enums\ProductSize::cases());

    $navigationItems = [
        ['label' => 'Teltis', 'href' => route('category.show', 'teltis')],
        ['label' => 'Nojumes', 'href' => route('category.show', 'nojumes')],
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
            Mēs piegādāsim atrakciju Jums ērtā vietā un laikā visā Latvijā<span class="hidden sm:inline"> |</span>
            <a href="tel:{{ str_replace(' ', '', config('site.phone')) }}" class="block whitespace-nowrap underline underline-offset-2 hover:text-gray-100 sm:inline">{{ config('site.phone') }}</a>
        </p>
    </div>

    <nav class="flex h-20 items-center px-4 lg:px-8">
        <div class="flex flex-1 justify-start lg:justify-center">
            <a href="{{ route('home') }}" wire:navigate class="shrink-0">
                <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="h-14 w-auto">
            </a>
        </div>

        <div class="hidden items-center gap-1 lg:flex">
            <div class="relative" x-data="{ open: false }" @click.outside="open = false">
                <button
                    type="button"
                    class="flex items-center gap-2 rounded-xl px-3.5 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 hover:text-gray-900"
                    @click="open = !open"
                    :aria-expanded="open"
                >
                    Piepūšamās atrakcijas
                    <x-public.icons.chevron-down class="size-5 transition-transform duration-200" x-bind:class="open && 'rotate-180'" />
                </button>

                <div
                    x-cloak
                    x-show="open"
                    x-transition.origin.top
                    class="absolute left-0 top-full z-20 mt-2 min-w-56 rounded-xl border border-gray-100 bg-white py-2 shadow-lg"
                >
                    @foreach ($attractionItems as $item)
                        <a href="{{ $item['href'] }}" wire:navigate class="block px-4 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            @foreach ($navigationItems as $item)
                <a href="{{ $item['href'] }}" @if ($item['href'] !== '#') wire:navigate @endif class="rounded-xl px-3.5 py-2 text-sm font-semibold text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>

        <div class="flex flex-1 justify-end">
        <button
            type="button"
            class="flex size-10 flex-col items-center justify-center gap-1.25 rounded-xl text-gray-600 hover:bg-gray-50 lg:hidden"
            @click="mobileMenuOpen = !mobileMenuOpen"
            :aria-expanded="mobileMenuOpen"
            aria-label="Izvēlne"
        >
            <span class="h-0.5 w-6 rounded-full bg-current transition-transform duration-300" :class="mobileMenuOpen && 'translate-y-1.75 rotate-45'"></span>
            <span class="h-0.5 w-6 rounded-full bg-current transition-opacity duration-300" :class="mobileMenuOpen && 'opacity-0'"></span>
            <span class="h-0.5 w-6 rounded-full bg-current transition-transform duration-300" :class="mobileMenuOpen && '-translate-y-1.75 -rotate-45'"></span>
        </button>
        </div>
    </nav>

    <div x-cloak x-show="mobileMenuOpen" x-collapse.duration.300ms class="border-t border-gray-100 px-4 pb-4 pt-2 lg:hidden">
        <a href="{{ route('category.show', 'piepusamas-atrakcijas') }}" wire:navigate class="block rounded-xl px-3.5 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 hover:text-gray-900">
            Piepūšamās atrakcijas
        </a>
        @foreach ($attractionItems as $item)
            <a href="{{ $item['href'] }}" wire:navigate class="block rounded-xl py-2.5 pl-7 pr-3.5 text-sm font-semibold text-gray-500 hover:bg-gray-50 hover:text-gray-900">
                {{ $item['label'] }}
            </a>
        @endforeach
        @foreach ($navigationItems as $item)
            <a href="{{ $item['href'] }}" @if ($item['href'] !== '#') wire:navigate @endif class="block rounded-xl px-3.5 py-2.5 text-sm font-semibold text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                {{ $item['label'] }}
            </a>
        @endforeach
    </div>
</header>
