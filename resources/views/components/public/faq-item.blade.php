@props([
    'question',
])

<div
    {{ $attributes->merge(['class' => 'w-full rounded-[20px] bg-white px-5 py-4 shadow-[0px_4px_2px_0px_rgba(0,0,0,0.11)]']) }}
    x-data="{ open: false }"
>
    <button
        type="button"
        class="flex w-full items-center justify-between gap-4 text-left"
        @click="open = !open"
        :aria-expanded="open"
    >
        <span class="font-heading text-xl font-semibold text-black lg:text-3xl lg:leading-[38px]">
            {{ $question }}
        </span>

        <span class="flex shrink-0 items-center justify-center rounded-xl bg-white p-2 text-gray-900 shadow-xs">
            <x-public.icons.chevron-down class="size-5 transition-transform duration-200" x-bind:class="open && 'rotate-180'" />
        </span>
    </button>

    <div x-cloak x-show="open" x-transition.origin.top class="pt-2">
        <p class="font-heading text-base font-semibold text-brand-dark lg:text-xl lg:leading-[30px]">
            {{ $slot }}
        </p>
    </div>
</div>
