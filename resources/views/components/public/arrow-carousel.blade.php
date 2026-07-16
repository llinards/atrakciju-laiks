{{-- Horizontally swipeable cards that stay a carousel on every breakpoint,
     with overlay prev/next arrows that disable at the ends
     (cf. card-carousel: dots on mobile, wrapping row on lg+). --}}
<div
    {{ $attributes->merge(['class' => 'relative']) }}
    x-data="{
        atStart: true,
        atEnd: true,
        overflows: false,
        update() {
            const track = this.$refs.track
            this.overflows = track.scrollWidth > track.clientWidth + 1
            this.atStart = track.scrollLeft <= 1
            this.atEnd = track.scrollLeft >= track.scrollWidth - track.clientWidth - 1
        },
        go(direction) {
            const track = this.$refs.track
            const gap = parseFloat(getComputedStyle(track).columnGap) || 0
            track.scrollBy({ left: direction * (track.children[0].offsetWidth + gap), behavior: 'smooth' })
        },
    }"
    x-init="update()"
    @resize.window.throttle.100ms="update()"
>
    <div
        x-ref="track"
        @scroll.throttle.50ms="update()"
        class="flex snap-x snap-mandatory gap-5 overflow-x-auto scroll-smooth px-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden [&>*]:shrink-0 [&>*]:snap-start [&>*]:basis-5/6 sm:[&>*]:basis-2/5 lg:px-0 lg:[&>*]:basis-[calc((100%-3*1.25rem)/3.5)]"
    >
        {{ $slot }}
    </div>

    <button
        type="button"
        class="absolute left-2 top-1/2 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-brand text-white shadow-md transition-colors hover:bg-brand-dark disabled:pointer-events-none disabled:opacity-40 lg:-left-4"
        @click="go(-1)"
        x-show="overflows"
        x-cloak
        x-bind:disabled="atStart"
        aria-label="Iepriekšējās kartītes"
    >
        <x-public.icons.chevron-down class="size-5 rotate-90" />
    </button>
    <button
        type="button"
        class="absolute right-2 top-1/2 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-brand text-white shadow-md transition-colors hover:bg-brand-dark disabled:pointer-events-none disabled:opacity-40 lg:-right-4"
        @click="go(1)"
        x-show="overflows"
        x-cloak
        x-bind:disabled="atEnd"
        aria-label="Nākamās kartītes"
    >
        <x-public.icons.chevron-down class="size-5 -rotate-90" />
    </button>
</div>
