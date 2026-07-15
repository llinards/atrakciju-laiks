{{-- Horizontally swipeable cards with snap + dots on mobile; wrapping centered row on lg+ --}}
<div
    {{ $attributes->merge(['class' => 'flex flex-col gap-6']) }}
    x-data="{
        active: 0,
        count: 0,
        init() {
            this.count = this.$refs.track.children.length
        },
        update() {
            const track = this.$refs.track
            this.active = Math.round(track.scrollLeft / (track.scrollWidth / this.count))
        },
        goTo(index) {
            const track = this.$refs.track
            track.scrollTo({ left: index * (track.scrollWidth / this.count), behavior: 'smooth' })
        },
    }"
>
    <div
        x-ref="track"
        @scroll.throttle.50ms="update()"
        class="flex snap-x snap-mandatory gap-5 overflow-x-auto scroll-smooth px-4 [scrollbar-width:none] [&::-webkit-scrollbar]:hidden [&>*]:shrink-0 [&>*]:snap-center [&>*]:basis-5/6 lg:flex-wrap lg:justify-center lg:gap-10 lg:overflow-visible lg:px-0 lg:[&>*]:shrink lg:[&>*]:basis-auto"
    >
        {{ $slot }}
    </div>

    <div class="flex items-center justify-center gap-2.5 lg:hidden" role="tablist" aria-label="Kartītes">
        <template x-for="index in count" :key="index">
            <button
                type="button"
                class="size-3 rounded-full transition-all duration-300"
                x-bind:class="active === index - 1 ? 'scale-110 bg-brand' : 'bg-brand/30 hover:bg-brand/50'"
                @click="goTo(index - 1)"
                x-bind:aria-label="'Kartīte ' + index"
                x-bind:aria-current="active === index - 1 ? 'true' : null"
            ></button>
        </template>
    </div>
</div>
