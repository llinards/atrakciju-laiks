<?php

use App\Actions\StoreOptimizedImage;
use App\Enums\ProductSize;
use App\Models\Category;
use App\Models\Product;
use Flux\Flux;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Produkti')] class extends Component {
    use WithFileUploads;

    public string $categoryFilter = '';

    public ?int $editingId = null;

    public string $categoryId = '';

    public string $name = '';

    public string $price = '';

    public ?string $discountPrice = null;

    public ?string $size = null;

    public ?TemporaryUploadedFile $image = null;

    public ?string $existingImageUrl = null;

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
     * Open the form modal for a new product.
     */
    public function create(): void
    {
        $this->resetForm();

        Flux::modal('product-form')->show();
    }

    /**
     * Open the form modal for an existing product.
     */
    public function edit(Product $product): void
    {
        $this->editingId = $product->id;
        $this->categoryId = (string) $product->category_id;
        $this->name = $product->name;
        $this->price = $product->price;
        $this->discountPrice = $product->discount_price;
        $this->size = $product->size?->value;
        $this->image = null;
        $this->existingImageUrl = $product->url();
        $this->resetValidation();

        Flux::modal('product-form')->show();
    }

    /**
     * Create or update the product being edited.
     */
    public function save(): void
    {
        // Livewire inputs submit '' instead of null; nullable rules only skip real null.
        $this->discountPrice = $this->discountPrice === '' ? null : $this->discountPrice;
        $this->size = $this->size === '' ? null : $this->size;

        $validated = $this->validate(
            rules: [
                'categoryId' => ['required', 'integer', Rule::exists(Category::class, 'id')],
                'name' => ['required', 'string', 'max:255'],
                'price' => ['required', 'numeric', 'decimal:0,2', 'min:0', 'max:999999.99'],
                'discountPrice' => ['nullable', 'numeric', 'decimal:0,2', 'lt:price', 'min:0'],
                'size' => ['nullable', Rule::enum(ProductSize::class)],
                'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ],
            messages: [
                'discountPrice.lt' => __('The discount price must be lower than the standard price.'),
            ],
            attributes: [
                'categoryId' => __('category attribute'),
                'name' => __('name attribute'),
                'price' => __('price attribute'),
                'discountPrice' => __('discount price attribute'),
                'size' => __('size attribute'),
                'image' => __('image attribute'),
            ],
        );

        $existing = $this->editingId !== null
            ? Product::query()->whereKey($this->editingId)->firstOrFail()
            : null;

        $path = $existing?->path;

        if ($this->image !== null) {
            if ($path !== null) {
                Storage::disk('public')->delete($path);
            }

            $path = app(StoreOptimizedImage::class)->handle($this->image, 'products', Product::IMAGE_WIDTH, Product::IMAGE_HEIGHT);
        }

        $data = [
            'category_id' => (int) $validated['categoryId'],
            'name' => $validated['name'],
            'price' => $validated['price'],
            'discount_price' => $validated['discountPrice'],
            'size' => $validated['size'],
            'path' => $path,
            'position' => $existing?->position ?? (int) Product::query()->max('position') + 1,
        ];

        $existing !== null ? $existing->update($data) : Product::query()->create($data);

        $this->resetForm();
        unset($this->products);

        Flux::modal('product-form')->close();
        Flux::toast(variant: 'success', text: __('Product saved.'));
    }

    /**
     * Remove the stored image without touching the rest of the product.
     */
    public function removeImage(Product $product): void
    {
        if ($product->path !== null) {
            Storage::disk('public')->delete($product->path);
        }

        $product->update(['path' => null]);
        unset($this->products);

        Flux::toast(variant: 'success', text: __('Product image removed.'));
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
     * Delete the product; its model event removes the stored image file.
     */
    public function delete(Product $product): void
    {
        $product->delete();
        unset($this->products);

        Flux::toast(variant: 'success', text: __('Product deleted.'));
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'categoryId', 'name', 'price', 'discountPrice', 'size', 'image', 'existingImageUrl');
        $this->resetValidation();
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

        <flux:button variant="primary" icon="plus" wire:click="create" data-test="add-product-button">
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
                                <p class="truncate font-medium">{{ $product->name }}</p>
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
                            <flux:button size="sm" icon="pencil-square" wire:click="edit({{ $product->id }})" aria-label="{{ __('Edit') }}" />
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

    <flux:modal name="product-form" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">
                {{ $editingId ? __('Edit product') : __('Add product') }}
            </flux:heading>

            <flux:select wire:model="categoryId" :label="__('Category')" :placeholder="__('Choose a category...')">
                @foreach ($this->categoryOptions as $category)
                    <flux:select.option value="{{ $category->id }}">{{ $category->title }}</flux:select.option>
                @endforeach
            </flux:select>

            <flux:input wire:model="name" :label="__('Name')" type="text" required />

            <div class="grid grid-cols-2 gap-4">
                <flux:input wire:model="price" :label="__('Price (EUR)')" type="number" step="0.01" min="0" required />

                <flux:input
                    wire:model="discountPrice"
                    :label="__('Discount price (EUR)')"
                    type="number"
                    step="0.01"
                    min="0"
                />
            </div>

            <flux:text size="sm" class="!-mt-4 text-zinc-500">
                {{ __('Discount price is optional. When set, it becomes the displayed price, the standard price is shown struck through, and the discount percent badge is computed automatically.') }}
            </flux:text>

            <flux:select wire:model="size" :label="__('Size')">
                <flux:select.option value="">—</flux:select.option>
                @foreach (\App\Enums\ProductSize::cases() as $case)
                    <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>

            <x-admin.image-field :image="$image" :existing-image-url="$existingImageUrl" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit" data-test="save-product-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
