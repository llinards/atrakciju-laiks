@php
    $contacts = [
        [
            'href' => 'tel:'.str_replace(' ', '', config('site.phone')),
            'label' => 'Zvanīt '.config('site.phone'),
            'icon' => 'public.icons.phone',
            'external' => false,
        ],
        [
            'href' => 'mailto:'.config('site.email'),
            'label' => 'Rakstīt e-pastu',
            'icon' => 'public.icons.mail',
            'external' => false,
        ],
        [
            'href' => config('site.facebook'),
            'label' => 'Facebook',
            'icon' => 'public.icons.facebook',
            'external' => true,
        ],
    ];
@endphp

<div {{ $attributes->merge(['class' => 'fixed bottom-24 right-6 z-30 md:bottom-auto md:right-7 md:top-1/2 md:-translate-y-1/2']) }}>
    {{-- Desktop: always-visible vertical rail --}}
    <div class="hidden flex-col items-center gap-5 rounded-full bg-brand px-3 py-6 shadow-lg md:flex">
        @foreach ($contacts as $contact)
            <a href="{{ $contact['href'] }}" @if ($contact['external']) target="_blank" rel="noopener" @endif
                class="text-white transition-opacity hover:opacity-80" aria-label="{{ $contact['label'] }}">
                <x-dynamic-component :component="$contact['icon']" class="size-6" />
            </a>
        @endforeach
    </div>

    {{-- Mobile: collapsed FAB that expands into the contact actions --}}
    <div class="flex flex-col items-center gap-3 md:hidden" x-data="{ open: false }">
        <div x-cloak x-show="open" x-transition.opacity.duration.200ms class="flex flex-col items-center gap-3">
            @foreach ($contacts as $contact)
                <a href="{{ $contact['href'] }}" @if ($contact['external']) target="_blank" rel="noopener" @endif
                    class="flex size-12 items-center justify-center rounded-full bg-brand text-white shadow-lg transition-colors hover:bg-brand-dark"
                    aria-label="{{ $contact['label'] }}">
                    <x-dynamic-component :component="$contact['icon']" class="size-5" />
                </a>
            @endforeach
        </div>

        <button type="button" @click="open = !open" :aria-expanded="open"
            class="flex size-14 items-center justify-center rounded-full bg-brand text-white shadow-lg transition-colors hover:bg-brand-dark"
            aria-label="Kontakti">
            <x-public.icons.phone x-show="!open" class="size-6" />
            <x-public.icons.close x-cloak x-show="open" class="size-6" />
        </button>
    </div>
</div>
