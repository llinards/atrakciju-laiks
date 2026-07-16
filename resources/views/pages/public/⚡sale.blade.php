<?php

use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::public')] class extends Component {
    use WithPagination;

    public function rendering(View $view): void
    {
        $view->title('Pārdošanas sadaļa');
    }

    /**
     * Only product visibility gates the sale section — categories are a
     * rental navigation concept, so a hidden category does not suppress
     * its for-sale products here.
     *
     * @return LengthAwarePaginator<int, Product>
     */
    #[Computed]
    public function products(): LengthAwarePaginator
    {
        return Product::query()
            ->visible()
            ->forSale()
            ->ordered()
            ->paginate(12);
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

        <x-public.section-heading tag="h1" align="left">
            Pārdošanas sadaļa
        </x-public.section-heading>

        @if ($this->products->isNotEmpty())
            <div class="grid gap-x-9 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->products as $product)
                    <x-public.product-card wire:key="product-{{ $product->id }}" :name="$product->name"
                        price-label="Pārdošanas cena:" :price="$product->formattedSalePrice()"
                        cta-label="Sazināties" :image="$product->url()" :image-alt="$product->name"
                        :href="route('sale.show', $product)" />
                @endforeach
            </div>

            <x-public.pagination :paginator="$this->products" class="border-t border-gray-200 pt-8" />
        @else
            <p class="py-16 text-center font-heading text-xl font-semibold text-gray-600">
                Pašlaik pārdošanā nav pieejamu produktu.
            </p>
        @endif
    </div>
</div>

<script>
    this.$js.scrollToTop = () => {
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
</script>
