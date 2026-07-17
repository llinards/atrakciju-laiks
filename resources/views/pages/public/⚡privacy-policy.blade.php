<?php

use App\Support\Seo;
use Illuminate\View\View;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use Whitecube\LaravelCookieConsent\Facades\Cookies;

new #[Layout('layouts::public')] #[Title('Privātuma politika')] class extends Component {
    public function rendering(View $view): void
    {
        app(Seo::class)
            ->describe('Privātuma politika — kā Atrakciju Laiks apstrādā tavus datus un kādas sīkdatnes izmanto šī vietne.')
            ->canonical(route('privacy'));
    }

    /**
     * The registered cookie categories for the cookies table.
     *
     * @return array<int, \Whitecube\LaravelCookieConsent\CookiesCategory>
     */
    public function cookieCategories(): array
    {
        return Cookies::getCategories();
    }
};
?>

<div class="px-4 pb-16 pt-8 lg:px-8">
    <div class="mx-auto flex max-w-3xl flex-col gap-8">
        <a href="{{ route('home') }}" wire:navigate
            class="inline-flex w-fit items-center gap-2 font-heading text-lg font-semibold text-gray-700 transition-colors hover:text-brand">
            <x-public.icons.arrow-left class="size-5" />
            Atpakaļ
        </a>

        <x-public.section-heading tag="h1" align="left"
            subtitle="Kā mēs apstrādājam tavus datus un kādas sīkdatnes izmanto šī vietne.">
            Privātuma politika
        </x-public.section-heading>

        <div class="flex flex-col gap-10 text-base leading-7 text-gray-700">
            <section class="flex flex-col gap-3">
                <h2 class="font-heading text-2xl font-bold text-gray-900">Vispārīga informācija</h2>
                <p>
                    Šo vietni uztur {{ config('app.name') }} ({{ config('site.address') }}).
                    Ja tev ir jautājumi par šo privātuma politiku vai savu datu apstrādi, sazinies ar mums pa
                    e-pastu <a href="mailto:{{ config('site.email') }}" class="font-semibold text-brand underline underline-offset-2">{{ config('site.email') }}</a>
                    vai pa tālruni <a href="tel:{{ str_replace(' ', '', config('site.phone')) }}" class="font-semibold text-brand underline underline-offset-2">{{ config('site.phone') }}</a>.
                </p>
            </section>

            <section class="flex flex-col gap-3">
                <h2 class="font-heading text-2xl font-bold text-gray-900">Kādus datus mēs apstrādājam</h2>
                <p>
                    Vietnes pārlūkošanai nav nepieciešams izveidot kontu vai norādīt personas datus.
                    Ja sazinies ar mums pa tālruni vai e-pastu, mēs apstrādājam tevis brīvprātīgi
                    sniegto kontaktinformāciju tikai tam, lai atbildētu uz tavu pieprasījumu un
                    vienotos par pakalpojuma sniegšanu.
                </p>
                <p>
                    Vietnes serveris drošības nolūkos īslaicīgi saglabā standarta tehniskos ierakstus
                    (piemēram, IP adresi un pieprasījuma laiku). Šie dati netiek izmantoti apmeklētāju
                    profilēšanai un netiek nodoti trešajām personām.
                </p>
            </section>

            <section class="flex flex-col gap-3">
                <h2 class="font-heading text-2xl font-bold text-gray-900">Sīkdatnes</h2>
                <p>
                    Šī vietne izmanto tikai tādas sīkdatnes, kas nepieciešamas tās darbībai.
                    Mēs neizmantojam analītikas, reklāmas vai citas izsekošanas sīkdatnes,
                    tāpēc sīkdatņu izmantošanai nav nepieciešama tava piekrišana — par tām
                    mēs tevi tikai informējam.
                </p>

                @foreach ($this->cookieCategories() as $category)
                    <div class="overflow-x-auto rounded-[22px] border border-gray-200 bg-white shadow-xs">
                        <table class="w-full min-w-md text-left text-sm">
                            <caption class="sr-only">{{ $category->title }}</caption>
                            <thead>
                                <tr class="border-b border-gray-200 bg-cream/60">
                                    <th scope="col" class="px-5 py-3.5 font-heading text-base font-bold text-gray-900">@lang('cookieConsent::cookies.cookie')</th>
                                    <th scope="col" class="px-5 py-3.5 font-heading text-base font-bold text-gray-900">@lang('cookieConsent::cookies.purpose')</th>
                                    <th scope="col" class="px-5 py-3.5 font-heading text-base font-bold text-gray-900">@lang('cookieConsent::cookies.duration')</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($category->getCookies() as $cookie)
                                    <tr class="border-b border-gray-100 last:border-0">
                                        <td class="px-5 py-3.5 font-mono text-[13px] text-gray-900">{{ $cookie->name }}</td>
                                        <td class="px-5 py-3.5">{{ $cookie->description }}</td>
                                        <td class="whitespace-nowrap px-5 py-3.5">
                                            {{ \Carbon\CarbonInterval::minutes($cookie->duration)->cascade()->locale('lv')->forHumans() }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endforeach
            </section>

            <section class="flex flex-col gap-3">
                <h2 class="font-heading text-2xl font-bold text-gray-900">Tavas tiesības</h2>
                <p>
                    Saskaņā ar Vispārīgo datu aizsardzības regulu (GDPR) tev ir tiesības pieprasīt
                    piekļuvi saviem datiem, to labošanu vai dzēšanu, kā arī iebilst pret to apstrādi.
                    Lai izmantotu šīs tiesības, sazinies ar mums, izmantojot augstāk norādīto
                    kontaktinformāciju. Tev ir arī tiesības iesniegt sūdzību Datu valsts inspekcijai
                    (<a href="https://www.dvi.gov.lv" target="_blank" rel="noopener" class="font-semibold text-brand underline underline-offset-2">www.dvi.gov.lv</a>).
                </p>
            </section>

            <p class="text-sm text-gray-500">Pēdējoreiz atjaunota: 2026. gada 17. jūlijā.</p>
        </div>
    </div>
</div>
