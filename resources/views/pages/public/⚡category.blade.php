<?php

use App\Enums\ProductSize;
use App\Models\Category;
use App\Support\Seo;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::public')] class extends Component {
    use WithPagination;

    public Category $category;

    #[Url(except: null)]
    public ?string $size = null;

    public function mount(Category $category): void
    {
        abort_unless($category->is_visible, 404);
    }

    public function rendering(View $view): void
    {
        $view->title($this->category->title);

        app(Seo::class)
            ->describe($this->category->description)
            ->canonical(route('category.show', $this->category))
            ->image($this->category->url())
            ->jsonLd([
                '@context' => 'https://schema.org',
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => 'Sākums', 'item' => route('home')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $this->category->title],
                ],
            ]);
    }

    /**
     * Toggle a size filter: selecting the active size clears it.
     */
    public function filterSize(string $size): void
    {
        $value = ProductSize::tryFrom($size)?->value;

        $this->size = $this->size === $value ? null : $value;
        $this->resetPage();
    }

    #[Computed]
    public function sizeFilter(): ?ProductSize
    {
        return ProductSize::tryFrom($this->size ?? '');
    }

    /**
     * @return LengthAwarePaginator<int, \App\Models\Product>
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        return $this->category->products()
            ->visible()
            ->when($this->sizeFilter, fn ($query, ProductSize $size) => $query->where('size', $size))
            ->ordered()
            ->paginate(12);
    }

    #[Computed]
    public function hasSizes(): bool
    {
        return $this->category->sizedProducts()->exists();
    }

    /**
     * Pagination swaps the grid in place, so restore the viewport to the top
     * instead of leaving the visitor at the bottom where the buttons sit.
     */
    public function updatedPaginators(int $page, string $pageName): void
    {
        $this->js('scrollToTop');
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

        <x-public.section-heading tag="h1" align="left" :subtitle="$category->description">
            {{ $category->title }}
        </x-public.section-heading>

        @if ($this->hasSizes)
            <div class="flex flex-col gap-2">
                <span class="text-sm font-semibold text-gray-600">Filtrēt</span>

                <div class="flex w-full gap-2 rounded-full border border-gray-200 bg-white p-2 shadow-xs"
                    role="group" aria-label="Filtrēt pēc izmēra">
                    @foreach (ProductSize::cases() as $case)
                        <button type="button" wire:click="filterSize('{{ $case->value }}')"
                            wire:key="size-{{ $case->value }}" aria-pressed="{{ $this->sizeFilter === $case ? 'true' : 'false' }}"
                            @class([
                                'flex-1 rounded-full border px-4 py-2.5 font-heading text-sm font-bold transition-colors sm:text-base',
                                'border-brand bg-brand text-white' => $this->sizeFilter === $case,
                                'border-gray-100 bg-white text-brand hover:bg-gray-50' => $this->sizeFilter !== $case,
                            ])>
                            {{ $case->label() }}
                        </button>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($this->products->isNotEmpty())
            <div class="grid gap-x-9 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->products as $product)
                    <x-public.product-card wire:key="product-{{ $product->id }}" :name="$product->name"
                        :price="$product->formattedPrice()" :original-price="$product->formattedOriginalPrice()"
                        :discount-percent="$product->discountPercent()" :is-new="$product->is_new" :image="$product->url()"
                        :image-alt="$product->name" :href="route('product.show', [$category, $product])" />
                @endforeach
            </div>

            <x-public.pagination :paginator="$this->products" class="border-t border-gray-200 pt-8" />
        @else
            <p class="py-16 text-center font-heading text-xl font-semibold text-gray-600">
                Šajā kategorijā pagaidām nav pieejamu produktu.
            </p>
        @endif
    </div>
</div>

<script>
    this.$js.scrollToTop = () => {
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
</script>
