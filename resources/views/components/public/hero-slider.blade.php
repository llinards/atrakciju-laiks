@props([
    'slides' => [],
])

<section
    {{ $attributes->merge(['class' => 'relative w-full overflow-hidden']) }}
    x-data="{
        active: 0,
        count: {{ count($slides) }},
        next() { this.active = (this.active + 1) % this.count },
        prev() { this.active = (this.active - 1 + this.count) % this.count },
    }"
>
    <div class="relative h-[320px] w-full md:h-[440px] lg:h-[558px]">
        @foreach ($slides as $slide)
            <img
                src="{{ $slide['src'] }}"
                alt="{{ $slide['alt'] ?? '' }}"
                x-cloak
                x-show="active === {{ $loop->index }}"
                x-transition.opacity.duration.500ms
                class="absolute inset-0 size-full object-cover"
                @if (! $loop->first) loading="lazy" @endif
            >
        @endforeach
    </div>

    @if (count($slides) > 1)
        <button
            type="button"
            class="absolute left-4 top-1/2 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-brand text-white shadow-md transition-colors hover:bg-brand-dark"
            @click="prev()"
            aria-label="Iepriekšējais attēls"
        >
            <x-public.icons.chevron-down class="size-5 rotate-90" />
        </button>
        <button
            type="button"
            class="absolute right-4 top-1/2 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-brand text-white shadow-md transition-colors hover:bg-brand-dark"
            @click="next()"
            aria-label="Nākamais attēls"
        >
            <x-public.icons.chevron-down class="size-5 -rotate-90" />
        </button>
    @endif
</section>
