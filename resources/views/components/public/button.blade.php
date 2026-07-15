@props([
    'variant' => 'sun',
    'href' => null,
])

@php
    $classes = match ($variant) {
        'sun' => 'border border-sun bg-sun text-white hover:bg-amber-500 hover:border-amber-500',
        'light' => 'border border-gray-50 bg-gray-50 text-brand hover:bg-white',
        'brand' => 'border border-brand bg-brand text-white hover:bg-brand-dark hover:border-brand-dark',
    };

    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @else type="{{ $attributes->get('type', 'button') }}" @endif
    {{ $attributes->except('type')->merge(['class' => "inline-flex items-center justify-center gap-2 rounded-xl px-5 py-3 text-base font-semibold shadow-xs transition-colors {$classes}"]) }}
>
    {{ $slot }}
</{{ $tag }}>
