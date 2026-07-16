@props([
    'subtitle' => null,
    'tag' => 'h2',
    'align' => 'center',
])

@php
    $alignClasses = match ($align) {
        'center' => 'items-center text-center',
        'left' => 'items-start text-left',
    };

    $subtitleClasses = match ($align) {
        'center' => 'max-w-2xl',
        'left' => 'max-w-4xl',
    };
@endphp

<div {{ $attributes->merge(['class' => "flex flex-col gap-5 {$alignClasses}"]) }}>
    <{{ $tag }} class="font-heading text-4xl font-bold leading-none tracking-[-0.06em] text-black lg:text-5xl">
        {{ $slot }}
    </{{ $tag }}>

    @if ($subtitle)
        <p class="{{ $subtitleClasses }} font-heading font-semibold tracking-[-0.04em] text-gray-600">
            {{ $subtitle }}
        </p>
    @endif
</div>
