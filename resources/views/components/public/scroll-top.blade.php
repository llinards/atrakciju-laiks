<button
    type="button"
    x-cloak
    x-data="{ visible: false }"
    x-show="visible"
    x-transition.opacity
    @scroll.window.throttle.100ms="visible = window.scrollY > 400"
    @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
    {{ $attributes->merge(['class' => 'fixed bottom-6 right-6 z-30 flex size-14 items-center justify-center rounded-full bg-sun text-white shadow-lg transition-colors hover:bg-amber-500']) }}
    aria-label="Uz augšu"
>
    <x-public.icons.chevron-down class="size-7 rotate-180" />
</button>
