{{-- Main image with prev/next arrows and a scrollable thumbnail strip; the
     PhotoSwipe lightbox behavior lives in the `productGallery` Alpine
     component registered in resources/js/public.js. --}}
@props([
    'images' => [],
])

<div {{ $attributes->merge(['class' => 'flex flex-col gap-4']) }} x-data="productGallery(@js($images))">
    {{-- On lg the main image stretches to whatever height the sibling grid
         column dictates, so both hero columns end flush. --}}
    <div class="relative aspect-[5/4] w-full overflow-clip rounded-2xl bg-gray-100 lg:aspect-auto lg:min-h-0 lg:flex-1">
        <img
            x-bind:src="images[active].src"
            x-bind:alt="images[active].alt"
            class="absolute inset-0 size-full cursor-zoom-in object-cover"
            @click="open()"
        >

        @if (count($images) > 1)
            <div
                class="absolute inset-x-4 top-1/2 flex -translate-y-1/2 justify-between lg:inset-auto lg:bottom-4 lg:right-4 lg:translate-y-0 lg:gap-2">
                <button
                    type="button"
                    class="flex size-9 items-center justify-center rounded-full bg-brand text-white shadow-md transition-colors hover:bg-brand-dark"
                    @click="prev()"
                    aria-label="Iepriekšējais attēls"
                >
                    <x-public.icons.chevron-down class="size-5 rotate-90" />
                </button>
                <button
                    type="button"
                    class="flex size-9 items-center justify-center rounded-full bg-brand text-white shadow-md transition-colors hover:bg-brand-dark"
                    @click="next()"
                    aria-label="Nākamais attēls"
                >
                    <x-public.icons.chevron-down class="size-5 -rotate-90" />
                </button>
            </div>
        @endif
    </div>

    @if (count($images) > 1)
        <div x-ref="thumbs"
            class="flex gap-3 overflow-x-auto scroll-smooth [scrollbar-width:none] [&::-webkit-scrollbar]:hidden"
            role="group" aria-label="Attēlu sīktēli">
            @foreach ($images as $image)
                <button
                    type="button"
                    class="aspect-[5/4] basis-1/3 shrink-0 overflow-clip rounded-xl border-2 transition-colors lg:basis-1/5"
                    x-bind:class="active === {{ $loop->index }} ? 'border-brand' : 'border-transparent hover:border-gray-200'"
                    @click="active = {{ $loop->index }}"
                    aria-label="Attēls {{ $loop->iteration }}"
                    x-bind:aria-current="active === {{ $loop->index }} ? 'true' : null"
                >
                    <img src="{{ $image['src'] }}" alt="{{ $image['alt'] }}" loading="lazy"
                        class="size-full object-cover">
                </button>
            @endforeach
        </div>
    @endif
</div>
