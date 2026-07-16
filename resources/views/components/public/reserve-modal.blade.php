@php
    $phone = config('site.phone');
    $phoneHref = 'tel:'.str_replace(' ', '', $phone);
    $email = config('site.email');
@endphp

<div
    x-cloak
    x-data="{ open: false }"
    x-show="open"
    @open-reserve-modal.window="open = true"
    @keydown.escape.window="open = false"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="reserve-modal-title"
>
    <div class="absolute inset-0 bg-gray-900/60" x-transition.opacity @click="open = false" aria-hidden="true"></div>

    <div
        x-show="open"
        x-transition.origin.center
        x-trap.inert.noscroll="open"
        class="relative flex w-full max-w-2xl flex-col items-center gap-6 rounded-3xl bg-white px-6 py-10 text-center shadow-xl sm:px-12"
    >
        <button
            type="button"
            @click="open = false"
            aria-label="Aizvērt"
            class="absolute top-4 right-4 rounded-xl p-2 text-gray-500 transition-colors hover:bg-gray-50 hover:text-gray-900"
        >
            <x-public.icons.close class="size-5" />
        </button>

        <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" class="h-16 w-auto">

        <h2 id="reserve-modal-title" class="font-heading text-3xl font-extrabold uppercase text-brand lg:text-4xl">
            Sazinies un rezervē!
        </h2>

        <p class="max-w-xl leading-7 text-gray-800">
            Sazinies ar mums - zvani vai raksti e-pastā, norādot datumu, vietu un izvēlēto atrakciju.
            Mēs pārbaudīsim pieejamību un vienosimies par detaļām!
        </p>

        <div class="flex w-full flex-col items-center justify-center gap-3 sm:flex-row">
            <x-public.button variant="brand" :href="$phoneHref">
                {{ $phone }}
            </x-public.button>

            <x-public.button variant="brand" href="mailto:{{ $email }}">
                {{ $email }}
            </x-public.button>
        </div>
    </div>
</div>
