@props([
    'slides' => [],
    'interval' => 6000,
])

<section
    {{ $attributes->merge(['class' => 'relative w-full overflow-hidden']) }}
    x-data="{
        active: 0,
        count: {{ count($slides) }},
        timer: null,
        next() { this.active = (this.active + 1) % this.count },
        prev() { this.active = (this.active - 1 + this.count) % this.count },
        play() {
            if (this.count < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                return
            }
            this.timer = setInterval(() => this.next(), {{ (int) $interval }})
        },
        pause() {
            clearInterval(this.timer)
        },
        restart() {
            this.pause()
            this.play()
        },
    }"
    x-init="play()"
    @mouseenter="pause()"
    @mouseleave="play()"
>
    <div class="relative h-[320px] w-full md:h-[440px] lg:h-[clamp(558px,65vh,820px)]">
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
            @click="prev(); restart()"
            aria-label="Iepriekšējais attēls"
        >
            <x-public.icons.chevron-down class="size-5 rotate-90" />
        </button>
        <button
            type="button"
            class="absolute right-4 top-1/2 flex size-9 -translate-y-1/2 items-center justify-center rounded-full bg-brand text-white shadow-md transition-colors hover:bg-brand-dark lg:right-20"
            @click="next(); restart()"
            aria-label="Nākamais attēls"
        >
            <x-public.icons.chevron-down class="size-5 -rotate-90" />
        </button>

        <div class="absolute bottom-4 left-1/2 flex -translate-x-1/2 items-center gap-2.5">
            @foreach ($slides as $slide)
                <button
                    type="button"
                    class="size-3 rounded-full shadow-sm transition-all duration-300"
                    x-bind:class="active === {{ $loop->index }} ? 'scale-125 bg-white' : 'bg-white/50 hover:bg-white/80'"
                    @click="active = {{ $loop->index }}; restart()"
                    aria-label="Attēls {{ $loop->iteration }}"
                    x-bind:aria-current="active === {{ $loop->index }} ? 'true' : null"
                ></button>
            @endforeach
        </div>
    @endif
</section>
