@props([
    'name',
    'price',
    'priceLabel' => 'Cena nomai no',
    'ctaLabel' => 'Apskatīt',
    'originalPrice' => null,
    'discountPercent' => null,
    'isNew' => false,
    'image' => null,
    'imageAlt' => '',
    'href' => '#',
])

<article
    {{ $attributes->merge(['class' => 'flex w-full flex-col gap-4 rounded-[22px] border border-gray-200 bg-white p-4 shadow-xs']) }}>
    <div class="relative aspect-[5/4] w-full overflow-clip rounded-2xl bg-gray-100">
        @if ($image)
            <img src="{{ $image }}" alt="{{ $imageAlt }}" class="absolute inset-0 size-full object-cover">
        @else
            <img src="{{ asset('images/pattern-1.svg') }}" alt="" aria-hidden="true"
                class="pointer-events-none absolute -left-10 -top-10 h-[150%] w-[130%] max-w-none opacity-8">
        @endif

        @if ($isNew)
            <span
                class="absolute left-3 top-3 rounded-full bg-brand px-3 py-1.5 text-sm font-semibold text-white shadow-xs">
                JAUNUMS!
            </span>
        @endif

        @if ($discountPercent)
            <span
                class="absolute right-3 top-3 rounded-full bg-white px-3 py-1.5 text-sm font-semibold text-gray-900 shadow-xs">
                {{ $discountPercent }}% atlaide
            </span>
        @endif
    </div>

    <div class="flex flex-1 flex-col items-start gap-2 px-1 pb-1">
        <h3 class="font-heading text-2xl font-bold leading-tight tracking-tight text-gray-900">
            {{ $name }}
        </h3>

        <p
            class="flex flex-wrap items-center gap-x-3 gap-y-1 font-heading text-2xl font-bold leading-tight tracking-tight text-brand">
            {{ $priceLabel }} {{ $price }}

            @if ($originalPrice)
                <span class="text-xl text-gray-400 line-through">{{ $originalPrice }}</span>
            @endif
        </p>

        <x-public.button variant="sun" :href="$href" class="mt-3 w-full" :wire:navigate="$href !== '#'">
            {{ $ctaLabel }}
        </x-public.button>
    </div>
</article>
