@props([
    'color' => 'splash',
    'title',
    'description' => null,
    'href' => '#',
    'image' => null,
    'imageAlt' => '',
])

@php
    $background = match ($color) {
        'splash' => 'bg-splash',
        'brand' => 'bg-brand',
        'sun' => 'bg-sun',
    };
@endphp

<div {{ $attributes->merge(['class' => "relative flex min-h-[219px] w-full max-w-[394px] overflow-clip rounded-[22px] p-[30px] lg:h-[219px] {$background}"]) }}>
    <img src="{{ asset('images/pattern-1.svg') }}" alt="" aria-hidden="true" class="pointer-events-none absolute -left-14 -top-16 h-[354px] w-[450px] max-w-none opacity-8">

    <div class="relative z-10 flex w-full flex-col items-center gap-5 py-4 text-center lg:w-[177px] lg:shrink-0 lg:items-start lg:justify-between lg:gap-0 lg:py-0 lg:text-left">
        <h3 class="font-heading text-3xl font-bold leading-none tracking-tight text-white">
            {{ $title }}
        </h3>

        @if ($description)
            <p class="text-sm font-semibold leading-5 text-white">
                {{ $description }}
            </p>
        @endif

        <a href="{{ $href }}" class="inline-flex items-center gap-2 rounded-xl border border-gray-50 bg-gray-50 px-3.5 py-2 text-sm font-semibold text-brand shadow-xs transition-colors hover:bg-white">
            Apskatīt
            <x-public.icons.arrow-right class="size-5" />
        </a>
    </div>

    @if ($image)
        <img src="{{ $image }}" alt="{{ $imageAlt }}" class="absolute right-0 top-1/2 hidden h-[90%] w-[45%] -translate-y-1/2 object-contain lg:block">
    @endif
</div>
