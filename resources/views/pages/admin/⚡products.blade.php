<?php

use App\Models\Category;
use App\Models\Product;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Produkti')] class extends Component {
    public string $categoryFilter = '';

    /**
     * @return Collection<int, Product>
     */
    #[Computed]
    public function products(): Collection
    {
        return Product::query()
            ->with('category')
            ->when($this->categoryFilter !== '', fn (Builder $query) => $query->where('category_id', (int) $this->categoryFilter))
            ->orderBy('category_id')
            ->ordered()
            ->get();
    }

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categoryOptions(): Collection
    {
        return Category::query()->ordered()->get();
    }

    /**
     * Persist a new order after a row is dragged to another position.
     * Ordering is always scoped to the dragged product's own category,
     * since the public pages order products per category.
     */
    public function sort(int $id, int $position): void
    {
        $categoryId = (int) Product::query()->whereKey($id)->value('category_id');

        $displayed = Product::query()
            ->when($this->categoryFilter !== '', fn (Builder $query) => $query->where('category_id', (int) $this->categoryFilter))
            ->orderBy('category_id')
            ->ordered()
            ->pluck('id')
            ->reject(fn (int $productId): bool => $productId === $id)
            ->values()
            ->all();

        array_splice($displayed, $position, 0, [$id]);

        $positions = Product::query()
            ->where('category_id', $categoryId)
            ->pluck('position', 'id');

        $newCategoryOrder = array_values(array_filter(
            $displayed,
            fn (int $productId): bool => $positions->has($productId),
        ));

        foreach ($newCategoryOrder as $index => $productId) {
            if ($positions[$productId] !== $index) {
                Product::query()->whereKey($productId)->update(['position' => $index]);
            }
        }

        unset($this->products);

        Flux::toast(variant: 'success', text: __('Order updated.'));
    }

    /**
     * Toggle whether the product is shown on the public site.
     */
    public function toggleVisibility(Product $product): void
    {
        $product->update(['is_visible' => ! $product->is_visible]);
        unset($this->products);

        Flux::toast(variant: 'success', text: $product->is_visible
            ? __('Product is now visible.')
            : __('Product is now hidden.'));
    }

    /**
     * Delete the product; its model event removes the stored image files.
     */
    public function delete(Product $product): void
    {
        $product->delete();
        unset($this->products);

        Flux::toast(variant: 'success', text: __('Product deleted.'));
    }
}; ?>

<section class="mx-auto w-full max-w-6xl">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Products') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">
            {{ __('Products shown on the public category pages. Hidden products are not shown.') }}
        </flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="mb-6 flex items-end justify-between gap-4">
        <div class="w-full max-w-xs">
            <flux:select wire:model.live="categoryFilter" :label="__('Category')">
                <flux:select.option value="">{{ __('All categories') }}</flux:select.option>
                @foreach ($this->categoryOptions as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->title }}</flux:select.option>
                @endforeach
            </flux:select>
        </div>

        <flux:button variant="primary" icon="plus" :href="route('products.create')" wire:navigate
            data-test="add-product-button">
            {{ __('Add product') }}
        </flux:button>
    </div>

    @if ($this->products->isNotEmpty())
        <flux:table>
            <flux:table.columns>
                <flux:table.column></flux:table.column>
                <flux:table.column>{{ __('Product') }}</flux:table.column>
                <flux:table.column>{{ __('Category') }}</flux:table.column>
                <flux:table.column>{{ __('Price') }}</flux:table.column>
                <flux:table.column>{{ __('Size') }}</flux:table.column>
                <flux:table.column>{{ __('Visible') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows wire:sort="sort">
                @foreach ($this->products as $product)
                    <flux:table.row :key="$product->id" wire:sort:item="{{ $product->id }}">
                        <flux:table.cell class="w-10">
                            <div wire:sort:handle class="cursor-grab text-zinc-400" aria-label="{{ __('Reorder') }}">
                                <flux:icon.bars-3 class="size-5" />
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="max-w-md">
                            <div class="flex items-center gap-3">
                                @if ($product->url())
                                    <img src="{{ $product->url() }}" alt="" class="size-10 shrink-0 rounded-lg object-cover">
                                @else
                                    <flux:icon.photo class="size-10 shrink-0 rounded-lg bg-zinc-100 p-2 text-zinc-400 dark:bg-zinc-700" />
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate font-medium">{{ $product->name }}</p>
                                    <p class="truncate text-sm text-zinc-500">/{{ $product->category->slug }}/{{ $product->slug }}</p>
                                </div>
                                @if ($product->is_new)
                                    <flux:badge size="sm" color="amber">JAUNUMS!</flux:badge>
                                @endif
                                @if ($product->is_for_sale)
                                    <flux:badge size="sm" color="green">Pārdošanā</flux:badge>
                                @endif
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $product->category->title }}</flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">
                            {{ $product->formattedPrice() }}
                            @if ($product->hasDiscount())
                                <s class="text-zinc-500">{{ $product->formattedOriginalPrice() }}</s>
                                <flux:badge size="sm" color="red">-{{ $product->discountPercent() }}%</flux:badge>
                            @endif
                        </flux:table.cell>
                        <flux:table.cell>{{ $product->size?->label() ?? '—' }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:switch
                                :checked="$product->is_visible"
                                wire:click="toggleVisibility({{ $product->id }})"
                                aria-label="{{ __('Visible') }}"
                            />
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">
                            <flux:button size="sm" icon="pencil-square" :href="route('products.edit', $product)"
                                wire:navigate aria-label="{{ __('Edit') }}" />
                            <flux:button
                                size="sm"
                                variant="danger"
                                icon="trash"
                                wire:click="delete({{ $product->id }})"
                                wire:confirm="{{ __('Delete this product?') }}"
                                aria-label="{{ __('Delete this product?') }}"
                            />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @else
        <flux:callout icon="information-circle" variant="secondary">
            <flux:callout.text>{{ __('No products yet.') }}</flux:callout.text>
        </flux:callout>
    @endif
</section>
