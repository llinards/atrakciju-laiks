<button
    type="button"
    x-cloak
    x-data="{ visible: false }"
    x-show="visible"
    x-transition.opacity
    @scroll.window.throttle.100ms="visible = window.scrollY > 400"
    @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
    {{-- Mobile size/offset mirror the contacts FAB so both sit on the hamburger's axis. --}}
    {{ $attributes->merge(['class' => 'fixed bottom-6 right-3 z-30 flex size-12 items-center justify-center rounded-full bg-sun text-white shadow-lg transition-colors hover:bg-amber-500 md:right-6 md:size-14']) }}
    aria-label="Uz augšu"
>
    <x-public.icons.chevron-down class="size-6 rotate-180 md:size-7" />
</button>
