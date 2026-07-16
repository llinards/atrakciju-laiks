<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component {
    public Category $category;

    public Product $product;

    public function mount(): void
    {
        abort_unless($this->category->is_visible && $this->product->is_visible, 404);
    }

    public function rendering(View $view): void
    {
        $view->title($this->product->name);
    }

    /**
     * The product's main image followed by its stored gallery. Products
     * without a gallery yet fall back to bundled demo placeholders.
     *
     * @return array<int, array{src: string, alt: string, width: int, height: int}>
     */
    public function galleryImages(): array
    {
        $main = $this->product->url() ?? asset('images/pattern-1.svg');

        $gallery = $this->product->images->map(fn (ProductImage $image): string => $image->url());

        $sources = $gallery->isNotEmpty()
            ? $gallery->prepend($main)
            : collect([$main, asset('images/about-1.png'), asset('images/hero-1.png'), asset('images/about-1.png'), asset('images/hero-1.png')]);

        return $sources
            ->map(
                fn (string $src): array => [
                    'src' => $src,
                    'alt' => $this->product->name,
                    'width' => Product::IMAGE_WIDTH,
                    'height' => Product::IMAGE_HEIGHT,
                ],
            )
            ->all();
    }

    /**
     * @return Collection<int, Product>
     */
    #[Computed]
    public function relatedProducts(): Collection
    {
        return $this->category
            ->products()
            ->visible()
            ->whereKeyNot($this->product->getKey())
            ->ordered()
            ->limit(8)
            ->get();
    }

    /**
     * Seeded/admin data when present, demo placeholders otherwise —
     * removed once every category's content has been migrated.
     *
     * @return array<string, string>
     */
    public function specs(): array
    {
        return $this->product->specs ?? [
            'Garums' => '9,5 m',
            'Platums' => '4,4 m',
            'Augstums' => '6,9 m',
            'Svars' => '234 kg',
            'Elektroapgāde' => '220–240V',
            'Sertifikāts' => 'ISO EN14960:2013',
        ];
    }

    /**
     * @return list<string>
     */
    public function includedItems(): array
    {
        return $this->product->included_items ?? [
            'atrakcijai piemērots gaisa pūtējs;',
            'elektroapgādes pagarinātājs 25 m vai 40 m garumā;',
            'apakšklājs / pārklājs;',
            'nepieciešamais drošības aprīkojums.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function rentalPrices(): array
    {
        return $this->product->rental_prices ?? [
            'Pirmdiena–ceturtdiena' => '180€',
            'Piektdiena–svētdiena un svētku dienas' => '180€',
            'Korporatīviem, sporta, publiskiem un bērnudārzu pasākumiem' => '290€',
        ];
    }

    /**
     * @return list<string>
     */
    public function rentalTermsParagraphs(): array
    {
        $terms = $this->product->rental_terms
            ?? 'Viena nomas diena ir no plkst. 8:00 / 12:00 līdz 18:00 / 20:00, vai pēc individuālas vienošanās. '
            .'Nomājot atrakciju uz vairākām dienām, katrai nākamajai dienai tiek piemērota atlaide. '
            .'Apkalpojam klientus visā Latvijā. Projekta piedāvājumus sagatavojam individuāli.';

        return $this->splitParagraphs($terms);
    }

    /**
     * @return list<string>
     */
    public function descriptionParagraphs(): array
    {
        if ($this->product->description === null) {
            return [];
        }

        return $this->splitParagraphs($this->product->description);
    }

    /**
     * @return list<string>
     */
    private function splitParagraphs(string $text): array
    {
        return preg_split('/\R{2,}/', trim($text)) ?: [];
    }
};
?>

<div class="px-4 pb-16 pt-8 lg:px-8">
    <div class="mx-auto flex max-w-7xl flex-col gap-8">
        <a href="{{ route('category.show', $category) }}" wire:navigate
            class="inline-flex w-fit items-center gap-2 font-heading text-lg font-semibold text-gray-700 transition-colors hover:text-brand">
            <x-public.icons.arrow-left class="size-5" />
            Atpakaļ
        </a>

        <div class="grid gap-6 lg:grid-cols-2 lg:grid-rows-[auto_1fr] lg:gap-x-12">
            <div class="flex flex-col gap-2 lg:col-start-2 lg:row-start-1">
                @if ($product->is_new)
                    <span class="w-fit rounded-full bg-brand px-3 py-1.5 text-sm font-semibold text-white shadow-xs">
                        JAUNUMS!
                    </span>
                @endif

                <h1 class="font-heading text-4xl font-bold leading-none tracking-[-0.06em] text-black lg:text-5xl">
                    {{ $product->name }}
                </h1>

                <p
                    class="flex flex-wrap items-center gap-x-3 gap-y-1 font-heading text-3xl font-bold leading-tight tracking-tight text-brand">
                    Nomas cena no {{ $product->formattedPrice() }}

                    @if ($product->formattedOriginalPrice())
                        <span class="text-2xl text-gray-400 line-through">{{ $product->formattedOriginalPrice() }}</span>
                    @endif
                </p>
            </div>

            <x-public.product-gallery :images="$this->galleryImages()" class="lg:col-start-1 lg:row-span-2 lg:row-start-1" />

            <div class="flex flex-col gap-6 lg:col-start-2 lg:row-start-2">
                <div class="flex flex-col gap-1">
                    <h2 class="font-heading text-lg font-bold text-gray-900">Tehniskā informācija:</h2>

                    <x-public.spec-table :rows="$this->specs()" />
                </div>

                <div class="flex flex-col gap-3">
                    <h2 class="font-heading text-lg font-bold text-gray-900">Nomas komplektā iekļauts:</h2>

                    <x-public.check-list :items="$this->includedItems()" />
                </div>

                <x-public.button variant="sun" href="#" class="w-full">
                    Rezervēt
                </x-public.button>
            </div>
        </div>

        <div class="flex flex-col gap-8" x-data="{ tab: 'about' }">
            <div class="flex flex-col gap-2 lg:mx-auto lg:w-full lg:max-w-2xl">
                <span class="text-sm font-semibold text-gray-600 lg:hidden">Filtrēt</span>

                <div class="flex w-full gap-2 rounded-full border border-gray-200 bg-white p-2 shadow-xs" role="tablist"
                    aria-label="Informācija par atrakciju">
                    <button type="button" role="tab" @click="tab = 'about'"
                        x-bind:aria-selected="tab === 'about' ? 'true' : 'false'"
                        class="flex-1 rounded-full border px-4 py-2.5 font-heading text-sm font-bold transition-colors sm:text-base"
                        x-bind:class="tab === 'about'
                            ?
                            'border-brand bg-brand text-white' :
                            'border-gray-100 bg-white text-brand hover:bg-gray-50'">
                        Par atrakciju
                    </button>
                    <button type="button" role="tab" @click="tab = 'rental'"
                        x-bind:aria-selected="tab === 'rental' ? 'true' : 'false'"
                        class="flex-1 rounded-full border px-4 py-2.5 font-heading text-sm font-bold transition-colors sm:text-base"
                        x-bind:class="tab === 'rental'
                            ?
                            'border-brand bg-brand text-white' :
                            'border-gray-100 bg-white text-brand hover:bg-gray-50'">
                        Noma un uzstādīšana
                    </button>
                </div>
            </div>

            <section x-show="tab === 'about'" x-cloak role="tabpanel"
                class="rounded-[22px] border border-gray-200 bg-white p-6 shadow-xs lg:p-10">
                @if ($this->descriptionParagraphs() !== [])
                    <div class="grid items-center gap-8 lg:grid-cols-2 lg:gap-16">
                        <div class="flex flex-col gap-4">
                            @foreach ($this->descriptionParagraphs() as $paragraph)
                                <p @class(['leading-7 text-gray-800', 'font-heading text-xl font-semibold text-gray-900' => $loop->first])>
                                    {{ $paragraph }}
                                </p>
                            @endforeach

                            @if ($product->suitability_items !== null)
                                <x-public.check-list class="mt-2" :items="$product->suitability_items" />
                            @endif
                        </div>

                        <img src="{{ $product->url() ?? asset('images/pattern-1.svg') }}" alt="{{ $product->name }}"
                            class="aspect-[5/4] w-full rounded-2xl object-cover">
                    </div>
                @else
                    {{-- Demo placeholder until this category's content is seeded.
                         The image spans both rows, so the text block hugs the row
                         boundary from above and the checklist from below — grouped
                         around the vertical center instead of drifting apart. --}}
                    <div class="grid items-center gap-8 lg:grid-cols-2 lg:gap-x-16 lg:gap-y-6">
                        <div class="flex flex-col gap-4 lg:self-end">
                            <h2
                                class="text-center font-heading text-4xl font-bold leading-none tracking-[-0.06em] text-black lg:text-left">
                                Košs dizains
                            </h2>

                            <p class="leading-7 text-gray-800">
                                Košais dizains un Minecraft tematika padara šo atrakciju par lielisku izvēli bērnu
                                ballītēm, skolu pasākumiem un lielākiem notikumiem.
                            </p>
                        </div>

                        <img src="{{ $product->url() ?? asset('images/pattern-1.svg') }}" alt="{{ $product->name }}"
                            class="aspect-[5/4] w-full rounded-2xl object-cover lg:col-start-2 lg:row-span-2 lg:row-start-1">

                        <x-public.check-list class="lg:col-start-1 lg:self-start" :items="[
                            'Bērniem no 3 gadu vecuma',
                            'Līdz 8 bērniem vienlaicīgi',
                            'Vienam bērnam līdz 70 kg',
                            'Bērniem līdz 160 cm augumam',
                        ]" />
                    </div>
                @endif
            </section>

            <section x-show="tab === 'rental'" x-cloak role="tabpanel"
                class="rounded-[22px] border border-gray-200 bg-white p-6 shadow-xs lg:p-10">
                <div class="grid items-center gap-8 lg:grid-cols-2 lg:gap-16">
                    <div class="flex flex-col gap-6">
                        <div class="flex flex-col gap-1">
                            <h2 class="font-heading text-lg font-bold text-gray-900">Cena par vienu nomas dienu:</h2>

                            <x-public.spec-table :rows="$this->rentalPrices()" />
                        </div>

                        @if ($product->rental_terms === null)
                            {{-- Demo placeholder until this category's content is seeded. --}}
                            <div class="flex flex-col gap-3">
                                <h2 class="font-heading text-lg font-bold text-gray-900">Klientam jānodrošina:</h2>

                                <x-public.check-list :items="[
                                    'zaļā zona atrakcijas novietošanai;',
                                    'ērta piekļuve ar vismaz 1,3 m platu ieeju vai piebraucamo ceļu;',
                                    '220–240V elektrības pieslēgums līdz 40 m attālumā.',
                                ]" />
                            </div>
                        @endif

                        <div class="flex flex-col gap-4">
                            @foreach ($this->rentalTermsParagraphs() as $paragraph)
                                <p class="leading-7 text-gray-800">{{ $paragraph }}</p>
                            @endforeach
                        </div>
                    </div>

                    <img src="{{ $product->url() ?? asset('images/pattern-1.svg') }}" alt="{{ $product->name }}"
                        class="aspect-[5/4] w-full rounded-2xl object-cover">
                </div>
            </section>
        </div>

        @if ($this->relatedProducts->isNotEmpty())
            <section class="flex flex-col gap-8 pt-8">
                <x-public.section-heading align="left">
                    Citas piepūšamās atrakcijas
                </x-public.section-heading>

                <x-public.arrow-carousel class="-mx-4 lg:mx-0">
                    @foreach ($this->relatedProducts as $related)
                        <x-public.product-card wire:key="related-{{ $related->id }}" :name="$related->name"
                            :price="$related->formattedPrice()" :original-price="$related->formattedOriginalPrice()" :discount-percent="$related->discountPercent()" :is-new="$related->is_new" :image="$related->url()"
                            :image-alt="$related->name" :href="route('product.show', [$category, $related])" />
                    @endforeach
                </x-public.arrow-carousel>
            </section>
        @endif
    </div>
</div>
