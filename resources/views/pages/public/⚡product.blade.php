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
     * The product's main image followed by its stored gallery.
     *
     * @return array<int, array{src: string, alt: string, width: int, height: int}>
     */
    public function galleryImages(): array
    {
        $main = $this->product->url() ?? asset('images/pattern-1.svg');

        return $this->product->images
            ->map(fn (ProductImage $image): string => $image->url())
            ->prepend($main)
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

    public function descriptionHtml(): ?string
    {
        return $this->richTextHtml($this->product->description);
    }

    public function rentalTermsHtml(): ?string
    {
        return $this->richTextHtml($this->product->rental_terms);
    }

    public function hasAboutTab(): bool
    {
        return $this->descriptionHtml() !== null;
    }

    public function hasRentalTab(): bool
    {
        return $this->product->rental_prices !== null || $this->rentalTermsHtml() !== null;
    }

    /**
     * Admin-managed content arrives as HTML from the rich text editor, while
     * legacy-seeded content is plain text with blank-line paragraph breaks —
     * the latter is escaped and wrapped so both render the same way.
     */
    private function richTextHtml(?string $text): ?string
    {
        if ($text === null || trim($text) === '') {
            return null;
        }

        if (str_contains($text, '<')) {
            return $text;
        }

        return collect(preg_split('/\R{2,}/', trim($text)) ?: [])
            ->map(fn (string $paragraph): string => '<p>'.e($paragraph).'</p>')
            ->implode('');
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
                @if ($product->specs !== null)
                    <div class="flex flex-col gap-1">
                        <h2 class="font-heading text-lg font-bold text-gray-900">Tehniskā informācija:</h2>

                        <x-public.spec-table :rows="$product->specs" />
                    </div>
                @endif

                @if ($product->included_items !== null)
                    <div class="flex flex-col gap-3">
                        <h2 class="font-heading text-lg font-bold text-gray-900">Nomas komplektā iekļauts:</h2>

                        <x-public.check-list :items="$product->included_items" />
                    </div>
                @endif

                <x-public.button variant="sun" href="#" class="w-full">
                    Rezervēt
                </x-public.button>
            </div>
        </div>

        @if ($this->hasAboutTab() || $this->hasRentalTab())
            <div class="flex flex-col gap-8" x-data="{ tab: '{{ $this->hasAboutTab() ? 'about' : 'rental' }}' }">
                @if ($this->hasAboutTab() && $this->hasRentalTab())
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
                @endif

                @if ($this->hasAboutTab())
                    <section x-show="tab === 'about'" x-cloak role="tabpanel"
                        class="rounded-[22px] border border-gray-200 bg-white p-6 shadow-xs lg:p-10">
                        <div class="grid items-center gap-8 lg:grid-cols-2 lg:gap-16">
                            <div
                                class="rich-text [&>p:first-child]:font-heading [&>p:first-child]:text-xl [&>p:first-child]:font-semibold [&>p:first-child]:text-gray-900">
                                {!! $this->descriptionHtml() !!}
                            </div>

                            <img src="{{ $product->url() ?? asset('images/pattern-1.svg') }}" alt="{{ $product->name }}"
                                class="aspect-[5/4] w-full rounded-2xl object-cover">
                        </div>
                    </section>
                @endif

                @if ($this->hasRentalTab())
                    <section x-show="tab === 'rental'" x-cloak role="tabpanel"
                        class="rounded-[22px] border border-gray-200 bg-white p-6 shadow-xs lg:p-10">
                        <div class="grid items-center gap-8 lg:grid-cols-2 lg:gap-16">
                            <div class="flex flex-col gap-6">
                                @if ($product->rental_prices !== null)
                                    <div class="flex flex-col gap-1">
                                        <h2 class="font-heading text-lg font-bold text-gray-900">Cena par vienu nomas dienu:</h2>

                                        <x-public.spec-table :rows="$product->rental_prices" />
                                    </div>
                                @endif

                                @if ($this->rentalTermsHtml() !== null)
                                    <div class="rich-text">
                                        {!! $this->rentalTermsHtml() !!}
                                    </div>
                                @endif
                            </div>

                            <img src="{{ $product->url() ?? asset('images/pattern-1.svg') }}" alt="{{ $product->name }}"
                                class="aspect-[5/4] w-full rounded-2xl object-cover">
                        </div>
                    </section>
                @endif
            </div>
        @endif

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
