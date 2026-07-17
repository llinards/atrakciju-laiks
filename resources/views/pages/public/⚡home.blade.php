<?php

use App\Models\Category;
use App\Models\Faq;
use App\Models\HeroSlide;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Layout('layouts::public')] #[Title('Atrakciju noma')] class extends Component {
    public function rendering(View $view): void
    {
        $seo = app(Seo::class)
            ->canonical(route('home'))
            ->image($this->heroSlides()[0]['src'])
            ->jsonLd([
            '@context' => 'https://schema.org',
            '@type' => 'LocalBusiness',
            'name' => config('app.name'),
            'description' => config('site.description'),
            'url' => route('home'),
            'telephone' => config('site.phone'),
            'email' => config('site.email'),
            'image' => asset('images/logo.png'),
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => config('site.address'),
                'addressCountry' => 'LV',
            ],
            'sameAs' => array_values(array_filter([
                config('site.facebook'),
                config('site.youtube'),
            ])),
        ]);

        if ($this->faqs->isNotEmpty()) {
            $seo->jsonLd([
                '@context' => 'https://schema.org',
                '@type' => 'FAQPage',
                'mainEntity' => $this->faqs
                    ->map(fn (Faq $faq): array => [
                        '@type' => 'Question',
                        'name' => $faq->question,
                        'acceptedAnswer' => [
                            '@type' => 'Answer',
                            'text' => $faq->answer,
                        ],
                    ])
                    ->all(),
            ]);
        }
    }

    /**
     * Hero slides managed from the admin panel, with bundled defaults
     * until at least one image has been uploaded.
     *
     * @return array<int, array{src: string, alt: string}>
     */
    public function heroSlides(): array
    {
        $slides = HeroSlide::query()
            ->ordered()
            ->get()
            ->map(fn (HeroSlide $slide): array => [
                'src' => $slide->url(),
                'alt' => config('app.name'),
            ])
            ->all();

        return $slides !== [] ? $slides : [
            ['src' => asset('images/hero-1.png'), 'alt' => 'Piepūšamā atrakcija zaļā pļavā'],
        ];
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::navigation();
    }

    /**
     * @return Collection<int, Faq>
     */
    #[Computed]
    public function faqs(): Collection
    {
        return Faq::query()->visible()->ordered()->get();
    }
};
?>

<div>
    <x-public.hero-slider :slides="$this->heroSlides()" />

    @if ($this->categories->isNotEmpty())
        <section class="px-4 pb-16 pt-14 lg:px-8">
            <div class="mx-auto flex max-w-7xl flex-col gap-14">
                <x-public.section-heading
                    subtitle="Izvēlies sev piemērotāko kategoriju bērnu ballītēm, pasākumiem un svinībām.">
                    Ko vēlies nomāt šodien?
                </x-public.section-heading>

                <x-public.card-carousel class="-mx-4 lg:mx-0">
                    @foreach ($this->categories as $category)
                        <x-public.category-card wire:key="category-{{ $category->id }}" :color="$category->color->value"
                            :title="$category->title" :description="$category->description" :href="route('category.show', $category->slug)"
                            :image="$category->url()" :image-alt="$category->title" />
                    @endforeach
                </x-public.card-carousel>
            </div>
        </section>
    @endif

    <section class="px-4 py-16 lg:px-8">
        <div class="mx-auto grid max-w-7xl items-center gap-10 lg:grid-cols-2 lg:gap-16">
            <img src="{{ asset('images/about-1.png') }}" alt="Bērni spēlējas piepūšamajā atrakcijā"
                class="aspect-square w-full rounded-[20px] object-cover">

            <div class="flex flex-col items-start gap-8">
                <h2 class="font-heading text-4xl font-bold leading-tight tracking-[-0.06em] text-black lg:text-5xl">
                    Prieks bez raizēm -<br>tā strādājam mēs
                </h2>

                <p class="font-heading text-xl font-semibold leading-7.5 text-gray-800">
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

                <x-public.button variant="sun" x-data @click="$dispatch('open-reserve-modal')">
                    Sazinies ar mums!
                </x-public.button>
            </div>
        </div>
    </section>

    @if ($this->faqs->isNotEmpty())
        <section class="px-4 py-16 lg:px-8">
            <div class="mx-auto flex max-w-7xl flex-col gap-14">
                <x-public.section-heading
                    subtitle="Atbildes uz jautājumiem, kas visbiežāk rodas par mūsu pakalpojumiem, rezervāciju un piegādi.">
                    Biežāk uzdotie jautājumi
                </x-public.section-heading>

                <div class="flex flex-col gap-5">
                    @foreach ($this->faqs as $faq)
                        <x-public.faq-item :question="$faq->question" wire:key="faq-{{ $faq->id }}">
                            {{ $faq->answer }}
                        </x-public.faq-item>
                    @endforeach
                </div>
            </div>
        </section>
    @endif
</div>
