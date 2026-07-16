<?php

use App\Actions\StoreOptimizedImage;
use App\Enums\ProductSize;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Produkts')] class extends Component {
    use WithFileUploads;

    public ?Product $product = null;

    public string $categoryId = '';

    public string $name = '';

    public string $price = '';

    public ?string $discountPrice = null;

    public ?string $size = null;

    public bool $isNew = false;

    public string $description = '';

    public string $rentalTerms = '';

    /** @var list<array{label: string, value: string}> */
    public array $specs = [];

    /** @var list<array{label: string, value: string}> */
    public array $rentalPrices = [];

    /** @var list<string> */
    public array $includedItems = [];

    public ?TemporaryUploadedFile $image = null;

    public ?string $existingImageUrl = null;

    /** @var array<int, TemporaryUploadedFile> */
    public array $galleryUploads = [];

    public function mount(?Product $product = null): void
    {
        $this->product = $product;

        if ($product === null) {
            $this->specs = [['label' => '', 'value' => '']];
            $this->rentalPrices = [['label' => '', 'value' => '']];
            $this->includedItems = [''];

            return;
        }

        $this->categoryId = (string) $product->category_id;
        $this->name = $product->name;
        $this->price = $product->price;
        $this->discountPrice = $product->discount_price;
        $this->size = $product->size?->value;
        $this->isNew = $product->is_new;
        $this->description = $this->toEditorHtml($product->description);
        $this->rentalTerms = $this->toEditorHtml($product->rental_terms);
        $this->specs = $this->toPairRows($product->specs);
        $this->rentalPrices = $this->toPairRows($product->rental_prices);
        $this->includedItems = $product->included_items ?? [''];
        $this->existingImageUrl = $product->url();
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
     * @return Collection<int, ProductImage>
     */
    #[Computed]
    public function galleryImages(): Collection
    {
        return $this->product?->images()->get() ?? new Collection;
    }

    /**
     * Append an empty row to one of the repeater fields.
     */
    public function addRow(string $field): void
    {
        abort_unless(in_array($field, ['specs', 'rentalPrices', 'includedItems'], true), 400);

        $this->{$field}[] = in_array($field, ['specs', 'rentalPrices'], true) ? ['label' => '', 'value' => ''] : '';
    }

    /**
     * Remove a row from one of the repeater fields.
     */
    public function removeRow(string $field, int $index): void
    {
        abort_unless(in_array($field, ['specs', 'rentalPrices', 'includedItems'], true), 400);

        unset($this->{$field}[$index]);
        $this->{$field} = array_values($this->{$field});
    }

    /**
     * Create or update the product; on create, redirect to the edit page so
     * the gallery section becomes available.
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
                'isNew' => ['boolean'],
                'description' => ['nullable', 'string'],
                'rentalTerms' => ['nullable', 'string'],
                'specs' => ['array'],
                'specs.*.label' => ['required_with:specs.*.value', 'nullable', 'string', 'max:255'],
                'specs.*.value' => ['required_with:specs.*.label', 'nullable', 'string', 'max:255'],
                'rentalPrices' => ['array'],
                'rentalPrices.*.label' => ['required_with:rentalPrices.*.value', 'nullable', 'string', 'max:255'],
                'rentalPrices.*.value' => ['required_with:rentalPrices.*.label', 'nullable', 'string', 'max:255'],
                'includedItems' => ['array'],
                'includedItems.*' => ['nullable', 'string', 'max:255'],
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
                'specs.*.label' => __('label attribute'),
                'specs.*.value' => __('value attribute'),
                'rentalPrices.*.label' => __('label attribute'),
                'rentalPrices.*.value' => __('value attribute'),
            ],
        );

        $path = $this->product?->path;

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
            'is_new' => $validated['isNew'],
            'description' => $this->normalizeRichText($validated['description']),
            'rental_terms' => $this->normalizeRichText($validated['rentalTerms']),
            'specs' => $this->normalizePairs($validated['specs']),
            'rental_prices' => $this->normalizePairs($validated['rentalPrices']),
            'included_items' => $this->normalizeList($validated['includedItems']),
            'path' => $path,
            'position' => $this->product?->position ?? (int) Product::query()->max('position') + 1,
        ];

        if ($this->product !== null) {
            $this->product->update($data);
            $this->existingImageUrl = $this->product->url();
            $this->image = null;

            Flux::toast(variant: 'success', text: __('Product saved.'));

            return;
        }

        $product = Product::query()->create($data);

        Flux::toast(variant: 'success', text: __('Product saved.'));

        $this->redirectRoute('products.edit', $product, navigate: true);
    }

    /**
     * Remove the stored main image without touching the rest of the product.
     */
    public function removeImage(): void
    {
        if ($this->product?->path !== null) {
            Storage::disk('public')->delete($this->product->path);
            $this->product->update(['path' => null]);
        }

        $this->existingImageUrl = null;

        Flux::toast(variant: 'success', text: __('Product image removed.'));
    }

    /**
     * Store the staged gallery uploads at the end of the gallery.
     */
    public function saveGalleryUploads(): void
    {
        abort_if($this->product === null, 404);

        $this->validate(
            rules: ['galleryUploads.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120']],
            attributes: ['galleryUploads.*' => __('image attribute')],
        );

        $position = (int) $this->product->images()->max('position');

        foreach ($this->galleryUploads as $upload) {
            $this->product->images()->create([
                'path' => app(StoreOptimizedImage::class)->handle($upload, 'products/gallery', Product::IMAGE_WIDTH, Product::IMAGE_HEIGHT),
                'position' => ++$position,
            ]);
        }

        $this->reset('galleryUploads');
        unset($this->galleryImages);

        Flux::toast(variant: 'success', text: __('Images added.'));
    }

    /**
     * Remove a staged upload before it is stored.
     */
    public function removeGalleryUpload(int $index): void
    {
        unset($this->galleryUploads[$index]);
        $this->galleryUploads = array_values($this->galleryUploads);
    }

    /**
     * Persist a new order after a gallery image is dragged to another position.
     */
    public function sortImages(int $id, int $position): void
    {
        abort_if($this->product === null, 404);

        $ids = $this->product->images()
            ->pluck('id')
            ->reject(fn (int $imageId): bool => $imageId === $id)
            ->values()
            ->all();

        array_splice($ids, $position, 0, [$id]);

        foreach ($ids as $index => $imageId) {
            ProductImage::query()->whereKey($imageId)->where('product_id', $this->product->id)->update(['position' => $index]);
        }

        unset($this->galleryImages);

        Flux::toast(variant: 'success', text: __('Order updated.'));
    }

    /**
     * Delete a gallery image; its model event removes the stored file.
     */
    public function deleteImage(ProductImage $image): void
    {
        abort_unless($image->product_id === $this->product?->id, 404);

        $image->delete();
        unset($this->galleryImages);

        Flux::toast(variant: 'success', text: __('Image deleted.'));
    }

    /**
     * Present stored plain text (from the legacy-site seeder) as HTML
     * paragraphs so the rich text editor can edit it.
     */
    private function toEditorHtml(?string $text): string
    {
        if ($text === null) {
            return '';
        }

        if (str_contains($text, '<')) {
            return $text;
        }

        return collect(preg_split('/\R{2,}/', trim($text)) ?: [])
            ->map(fn (string $paragraph): string => '<p>'.e($paragraph).'</p>')
            ->implode('');
    }

    private function normalizeRichText(?string $html): ?string
    {
        return $html !== null && trim(strip_tags($html)) !== '' ? $html : null;
    }

    /**
     * @return list<array{label: string, value: string}>
     */
    private function toPairRows(?array $pairs): array
    {
        $rows = collect($pairs ?? [])
            ->map(fn (string $value, string $label): array => ['label' => $label, 'value' => $value])
            ->values()
            ->all();

        return $rows !== [] ? $rows : [['label' => '', 'value' => '']];
    }

    /**
     * @param  array<int, array{label: ?string, value: ?string}>  $rows
     * @return array<string, string>|null
     */
    private function normalizePairs(array $rows): ?array
    {
        $pairs = [];

        foreach ($rows as $row) {
            $label = trim($row['label'] ?? '');
            $value = trim($row['value'] ?? '');

            if ($label !== '' && $value !== '') {
                $pairs[$label] = $value;
            }
        }

        return $pairs !== [] ? $pairs : null;
    }

    /**
     * @param  array<int, ?string>  $rows
     * @return list<string>|null
     */
    private function normalizeList(array $rows): ?array
    {
        $items = array_values(array_filter(
            array_map(fn (?string $item): string => trim($item ?? ''), $rows),
            fn (string $item): bool => $item !== '',
        ));

        return $items !== [] ? $items : null;
    }
}; ?>

<section class="mx-auto w-full max-w-3xl">
    <div class="mb-6 flex items-center gap-3">
        <flux:button variant="ghost" icon="arrow-left" :href="route('products.index')" wire:navigate
            aria-label="{{ __('Back') }}" />

        <div class="min-w-0">
            <flux:heading size="xl" level="1">
                {{ $product ? $product->name : __('Add product') }}
            </flux:heading>

            @if ($product)
                <flux:subheading>/{{ $product->category->slug }}/{{ $product->slug }}</flux:subheading>
            @endif
        </div>
    </div>

    <form wire:submit="save" class="space-y-8">
        <div class="space-y-6">
            <flux:heading size="lg" level="2">{{ __('Basic information') }}</flux:heading>

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

            <flux:checkbox wire:model="isNew" :label="__('New product')"
                :description="__('Shows a JAUNUMS! badge on the public site — no need to put it in the name.')" />

            <x-admin.image-field :image="$image" :existing-image-url="$existingImageUrl" />

            @if ($existingImageUrl && $product?->path)
                <flux:button size="sm" variant="ghost" icon="trash" wire:click="removeImage">
                    {{ __('Remove image') }}
                </flux:button>
            @endif
        </div>

        <flux:separator variant="subtle" />

        <div class="space-y-6">
            <flux:heading size="lg" level="2">{{ __('Description') }}</flux:heading>

            <flux:editor wire:model="description" :label="__('Description')"
                :description="__('Shown in the Par atrakciju tab of the product page. Bullet lists render with check icons, e.g. age and weight limits.')"
                toolbar="bold italic underline | bullet ordered | link | undo redo" />
        </div>

        <flux:separator variant="subtle" />

        <div class="space-y-6">
            <flux:heading size="lg" level="2">{{ __('Technical information') }}</flux:heading>

            <flux:field>
                <flux:description>{{ __('Label and value rows shown in the technical information table.') }}</flux:description>

                <div class="space-y-2">
                    @foreach ($specs as $index => $spec)
                        <div class="flex items-center gap-2" wire:key="spec-{{ $index }}">
                            <div class="flex-1">
                                <flux:input wire:model="specs.{{ $index }}.label" type="text" :placeholder="__('Label')" />
                            </div>
                            <div class="flex-1">
                                <flux:input wire:model="specs.{{ $index }}.value" type="text" :placeholder="__('Value')" />
                            </div>
                            <flux:button size="sm" variant="ghost" icon="trash"
                                wire:click="removeRow('specs', {{ $index }})" aria-label="{{ __('Remove row') }}" />
                        </div>
                    @endforeach
                </div>

                <flux:button size="sm" icon="plus" wire:click="addRow('specs')">
                    {{ __('Add row') }}
                </flux:button>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Included in the rental') }}</flux:label>
                <flux:description>{{ __('Checklist next to the technical information, e.g. the air blower.') }}</flux:description>

                <div class="space-y-2">
                    @foreach ($includedItems as $index => $item)
                        <div class="flex items-center gap-2" wire:key="included-{{ $index }}">
                            <div class="flex-1">
                                <flux:input wire:model="includedItems.{{ $index }}" type="text" />
                            </div>
                            <flux:button size="sm" variant="ghost" icon="trash"
                                wire:click="removeRow('includedItems', {{ $index }})" aria-label="{{ __('Remove row') }}" />
                        </div>
                    @endforeach
                </div>

                <flux:button size="sm" icon="plus" wire:click="addRow('includedItems')">
                    {{ __('Add row') }}
                </flux:button>
            </flux:field>
        </div>

        <flux:separator variant="subtle" />

        <div class="space-y-6">
            <flux:heading size="lg" level="2">{{ __('Rental information') }}</flux:heading>

            <flux:field>
                <flux:label>{{ __('Rental prices') }}</flux:label>
                <flux:description>{{ __('Price rows in the Noma un uzstādīšana tab, e.g. weekday and weekend rates.') }}</flux:description>

                <div class="space-y-2">
                    @foreach ($rentalPrices as $index => $row)
                        <div class="flex items-center gap-2" wire:key="rental-price-{{ $index }}">
                            <div class="flex-1">
                                <flux:input wire:model="rentalPrices.{{ $index }}.label" type="text" :placeholder="__('Label')" />
                            </div>
                            <div class="w-32 shrink-0">
                                <flux:input wire:model="rentalPrices.{{ $index }}.value" type="text" :placeholder="__('Value')" />
                            </div>
                            <flux:button size="sm" variant="ghost" icon="trash"
                                wire:click="removeRow('rentalPrices', {{ $index }})" aria-label="{{ __('Remove row') }}" />
                        </div>
                    @endforeach
                </div>

                <flux:button size="sm" icon="plus" wire:click="addRow('rentalPrices')">
                    {{ __('Add row') }}
                </flux:button>
            </flux:field>

            <flux:editor wire:model="rentalTerms" :label="__('Rental terms')"
                :description="__('Shown in the Noma un uzstādīšana tab below the prices.')"
                toolbar="bold italic underline | bullet ordered | link | undo redo" />
        </div>

        <div class="flex justify-end gap-2">
            <flux:button variant="ghost" :href="route('products.index')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>

            <flux:button variant="primary" type="submit" data-test="save-product-button">
                {{ __('Save') }}
            </flux:button>
        </div>
    </form>

    @if ($product)
        <flux:separator variant="subtle" class="my-8" />

        <div class="space-y-6">
            <div>
                <flux:heading size="lg" level="2">{{ __('Gallery') }}</flux:heading>
                <flux:subheading>{{ __('Additional product page images. Drag to reorder.') }}</flux:subheading>
            </div>

            @if ($this->galleryImages->isNotEmpty())
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3" wire:sort="sortImages">
                    @foreach ($this->galleryImages as $galleryImage)
                        <div wire:key="gallery-{{ $galleryImage->id }}" wire:sort:item="{{ $galleryImage->id }}"
                            class="group relative overflow-clip rounded-lg border border-zinc-200 dark:border-white/10">
                            <img src="{{ $galleryImage->url() }}" alt="" class="aspect-[5/4] w-full object-cover">

                            <div wire:sort:handle
                                class="absolute left-2 top-2 cursor-grab rounded-md bg-white/90 p-1.5 text-zinc-500 shadow-xs"
                                aria-label="{{ __('Reorder') }}">
                                <flux:icon.bars-3 class="size-4" />
                            </div>

                            <div class="absolute right-2 top-2">
                                <flux:button size="sm" variant="danger" icon="trash"
                                    wire:click="deleteImage({{ $galleryImage->id }})"
                                    wire:confirm="{{ __('Delete this image?') }}" aria-label="{{ __('Delete this image?') }}" />
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($galleryUploads !== [])
                <div class="space-y-2">
                    @foreach ($galleryUploads as $index => $upload)
                        <flux:file-item :image="$upload->temporaryUrl()" :heading="$upload->getClientOriginalName()"
                            wire:key="gallery-upload-{{ $index }}">
                            <flux:file-item.remove wire:click="removeGalleryUpload({{ $index }})" />
                        </flux:file-item>
                    @endforeach
                </div>

                <flux:button variant="primary" icon="plus" wire:click="saveGalleryUploads">
                    {{ __('Add images') }}
                </flux:button>
            @endif

            <flux:file-upload wire:model="galleryUploads" multiple>
                <flux:file-upload.dropzone
                    :heading="__('Drop files here or click to browse')"
                    :text="__('JPG, PNG, WEBP up to 5MB - optimized automatically')"
                />
            </flux:file-upload>

            <flux:error name="galleryUploads.*" />
        </div>
    @endif
</section>
