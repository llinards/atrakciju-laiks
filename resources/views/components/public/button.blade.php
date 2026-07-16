@props([
    'variant' => 'sun',
    'size' => 'base',
    'href' => null,
])

@php
    $classes = match ($variant) {
        'sun' => 'border border-sun bg-sun text-white hover:bg-amber-500 hover:border-amber-500',
        'light' => 'border border-gray-50 bg-gray-50 text-brand hover:bg-white',
        'brand' => 'border border-brand bg-brand text-white hover:bg-brand-dark hover:border-brand-dark',
        'outline' => 'border border-gray-200 bg-white text-gray-700 hover:bg-gray-50',
    };

    $sizeClasses = match ($size) {
        'base' => 'px-5 py-3 text-base',
        'sm' => 'px-3.5 py-2 text-sm',
    };

    $tag = $href ? 'a' : 'button';
@endphp

<{{ $tag }}
    @if ($href) href="{{ $href }}" @else type="{{ $attributes->get('type', 'button') }}" @endif
    {{ $attributes->except('type')->merge(['class' => "inline-flex items-center justify-center gap-2 rounded-xl font-semibold shadow-xs transition-colors disabled:pointer-events-none disabled:opacity-40 {$sizeClasses} {$classes}"]) }}
>
    {{ $slot }}
</{{ $tag }}>
