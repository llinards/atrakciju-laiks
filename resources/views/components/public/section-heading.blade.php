@props([
    'subtitle' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center gap-5 text-center']) }}>
    <h2 class="font-heading text-4xl font-bold leading-none tracking-[-0.06em] text-black lg:text-5xl">
        {{ $slot }}
    </h2>

    @if ($subtitle)
        <p class="max-w-2xl font-heading font-semibold tracking-[-0.04em] text-gray-600">
            {{ $subtitle }}
        </p>
    @endif
</div>
