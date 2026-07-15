<?php

use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Sākums')] class extends Component {
    //
};
?>

<div>
    <x-public.hero-slider :slides="[
        ['src' => asset('images/hero-1.png'), 'alt' => 'Piepūšamā atrakcija zaļā pļavā'],
        ['src' => asset('images/hero-2.png'), 'alt' => 'Minecraft piepūšamā atrakcija ar slidkalniņu'],
    ]" />

    <section class="px-4 pb-16 pt-14 lg:px-8">
        <div class="mx-auto flex max-w-7xl flex-col gap-14">
            <x-public.section-heading
                subtitle="Izvēlies sev piemērotāko kategoriju bērnu ballītēm, pasākumiem un svinībām.">
                Ko vēlies nomāt šodien?
            </x-public.section-heading>

            <div class="flex flex-wrap justify-center gap-10">
                <x-public.category-card color="splash" title="Piepūšamās atrakcijas"
                    description="Jautrībai, kustībai un bērnu priekam" href="#" :image="asset('images/category-atrakcijas.png')"
                    image-alt="Piepūšamā atrakcija" />
                <x-public.category-card color="brand" title="Teltis"
                    description="Ērtam pasākumam jebkuros laikapstākļos" href="#" :image="asset('images/category-teltis.png')"
                    image-alt="Pasākumu telts" />
                <x-public.category-card color="sun" title="Nojumes"
                    description="Praktisks risinājums svinībām un pasākumiem ārā" href="#" :image="asset('images/category-nojumes.png')"
                    image-alt="Nojume" />
            </div>
        </div>
    </section>

    <section class="px-4 py-16 lg:px-8">
        <div class="mx-auto grid max-w-7xl items-center gap-10 lg:grid-cols-2 lg:gap-16">
            <img src="{{ asset('images/about-1.png') }}" alt="Bērni spēlējas piepūšamajā atrakcijā"
                class="aspect-square w-full rounded-[20px] object-cover">

            <div class="flex flex-col items-start gap-8">
                <h2 class="font-heading text-4xl font-bold leading-tight tracking-[-0.06em] text-black lg:text-5xl">
                    Prieks bez raizēm -<br>tā strādājam mēs
                </h2>

                <p class="font-heading text-xl font-semibold leading-[30px] text-gray-800">
                    Svētku plānošana ir rūpīgi pārdomāts un īpašs process - kādam tie var būt pat dzīves lielākie
                    svētki.
                    Tieši tāpēc Atrakciju Laiks ir radīts, lai palīdzētu šos mirkļus padarīt vēl priecīgākus, vieglākus
                    un neaizmirstamākus.
                </p>

                <ul class="flex flex-col gap-2.5">
                    @foreach (['Piegāde visā Latvijā', 'Drošs un kvalitatīvs inventārs', 'Atrakcijas dažādiem pasākumiem', 'Ātra un ērta rezervācija'] as $benefit)
                        <li class="flex items-start gap-3">
                            <x-public.icons.check class="size-6 shrink-0" />
                            <span class="leading-6 text-gray-800">{{ $benefit }}</span>
                        </li>
                    @endforeach
                </ul>

                <x-public.button variant="sun" href="mailto:{{ config('site.email') }}">
                    Sazinies ar mums!
                </x-public.button>
            </div>
        </div>
    </section>

    <section class="px-4 py-16 lg:px-8">
        <div class="mx-auto flex max-w-7xl flex-col gap-14">
            <x-public.section-heading
                subtitle="Atbildes uz jautājumiem, kas visbiežāk rodas par mūsu pakalpojumiem, rezervāciju un piegādi.">
                Biežāk uzdotie jautājumi
            </x-public.section-heading>

            <div class="flex flex-col gap-5">
                <x-public.faq-item question="Vai ir iespējama piegāde uz pasākuma vietu?">
                    Jā, nodrošinām piegādi uz pasākuma norises vietu. Piegādes izmaksas un iespējas atkarīgas no
                    atrašanās vietas un izvēlētā inventāra.
                </x-public.faq-item>

                <x-public.faq-item question="Vai ir iespējama saziņa ārpus darba laika?">
                    Jā, sazinieties ar mums pa tālruni vai e-pastu, un mēs atbildēsim, tiklīdz tas būs iespējams.
                </x-public.faq-item>

                <x-public.faq-item question="Ko darīt, ja esmu rezervējis, bet mani plāni mainās?">
                    Sazinieties ar mums pēc iespējas ātrāk, un mēs kopā atradīsim risinājumu - mainīsim rezervācijas
                    datumu vai atcelsim to.
                </x-public.faq-item>
            </div>
        </div>
    </section>
</div>
