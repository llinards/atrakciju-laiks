@props([
    'title',
    'image' => null,
    'href',
])

<article {{ $attributes->merge(['class' => 'flex w-full flex-col gap-4 rounded-[22px] border border-gray-200 bg-white p-4 shadow-xs']) }}>
    <div class="relative aspect-[5/4] w-full overflow-clip rounded-2xl bg-gray-100">
        @if ($image)
            <img src="{{ $image }}" alt="{{ $title }}" loading="lazy" class="absolute inset-0 size-full object-cover">
        @else
            <img src="{{ asset('images/pattern-1.svg') }}" alt="" aria-hidden="true"
                class="pointer-events-none absolute -left-10 -top-10 h-[150%] w-[130%] max-w-none opacity-8">
        @endif
    </div>

    <div class="flex flex-1 flex-col items-center gap-2 px-1 pb-1 text-center">
        <h3 class="font-heading text-2xl font-bold leading-tight tracking-tight text-gray-900">
            {{ $title }}
        </h3>

        <x-public.button variant="sun" :href="$href" class="mt-3 w-full" wire:navigate>
            Skatīt
        </x-public.button>
    </div>
</article>
