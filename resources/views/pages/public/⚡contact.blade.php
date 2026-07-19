<?php

use App\Support\Seo;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Kontakti')] class extends Component {
    public function rendering(View $view): void
    {
        app(Seo::class)->describe('Sazinies ar mums — palīdzēsim izvēlēties atrakcijas Taviem svētkiem. Zvani, raksti vai apciemo mūs klātienē.')->canonical(route('contact'));
    }
};
?>

<div class="px-4 pb-16 pt-8 lg:px-8">
    <div class="mx-auto flex max-w-7xl flex-col gap-8">
        <a href="{{ route('home') }}" wire:navigate
            class="inline-flex w-fit items-center gap-2 font-heading text-lg font-semibold text-gray-700 transition-colors hover:text-brand">
            <x-public.icons.arrow-left class="size-5" />
            Atpakaļ
        </a>

        <x-public.section-heading tag="h1" align="left"
            subtitle="Sazinies ar mums — palīdzēsim izvēlēties atrakcijas Taviem svētkiem.">
            Kontakti
        </x-public.section-heading>

        <div class="grid gap-8 lg:grid-cols-2 lg:gap-16">
            <div
                class="flex flex-col items-start gap-6 rounded-[22px] border border-gray-200 bg-white p-6 shadow-xs lg:p-10">
                <a href="tel:{{ str_replace(' ', '', config('site.phone')) }}"
                    class="flex items-center gap-3 font-heading text-xl font-semibold text-gray-800 transition-colors hover:text-brand">
                    <x-public.icons.phone class="size-6 shrink-0 text-brand" />
                    {{ config('site.phone') }}
                </a>

                <a href="mailto:{{ config('site.email') }}"
                    class="flex items-center gap-3 font-heading text-xl font-semibold text-gray-800 transition-colors hover:text-brand">
                    <x-public.icons.mail class="size-6 shrink-0 text-brand" />
                    {{ config('site.email') }}
                </a>

                <p class="flex items-start gap-3 font-heading text-xl font-semibold leading-7 text-gray-800">
                    <x-public.icons.map-pin class="size-6 shrink-0 text-brand" />
                    <x-public.address />
                </p>

                <div class="flex items-center gap-4">
                    <a href="{{ config('site.facebook') }}" target="_blank" rel="noopener"
                        class="text-gray-900 transition-opacity hover:opacity-70" aria-label="Facebook">
                        <x-public.icons.facebook class="size-6" />
                    </a>

                    <a href="{{ config('site.youtube') }}" target="_blank" rel="noopener"
                        class="text-gray-900 transition-opacity hover:opacity-70" aria-label="YouTube">
                        <x-public.icons.youtube class="size-6" />
                    </a>
                </div>

                <x-public.button variant="sun" x-data @click="$dispatch('open-reserve-modal')">
                    Sazinies ar mums!
                </x-public.button>
            </div>

            <div class="relative h-80 overflow-hidden rounded-[22px] border border-gray-200 shadow-xs lg:h-auto">
                <iframe src="https://www.google.com/maps?q={{ urlencode(config('site.address')) }}&output=embed"
                    title="{{ config('site.address') }}" loading="lazy" referrerpolicy="no-referrer-when-downgrade"
                    allowfullscreen class="absolute inset-0 size-full"></iframe>
            </div>
        </div>
    </div>
</div>
