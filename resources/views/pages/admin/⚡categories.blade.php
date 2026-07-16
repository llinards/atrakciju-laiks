<?php

use App\Actions\StoreOptimizedImage;
use App\Enums\CategoryColor;
use App\Models\Category;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new #[Title('Kategorijas')] class extends Component {
    use WithFileUploads;

    public ?int $editingId = null;

    public string $title = '';

    public string $description = '';

    public ?TemporaryUploadedFile $image = null;

    public ?string $existingImageUrl = null;

    /**
     * @return Collection<int, Category>
     */
    #[Computed]
    public function categories(): Collection
    {
        return Category::query()->withCount('products')->ordered()->get();
    }

    /**
     * Open the form modal for a new category.
     */
    public function create(): void
    {
        $this->resetForm();

        Flux::modal('category-form')->show();
    }

    /**
     * Open the form modal for an existing category.
     */
    public function edit(Category $category): void
    {
        $this->editingId = $category->id;
        $this->title = $category->title;
        $this->description = $category->description ?? '';
        $this->image = null;
        $this->existingImageUrl = $category->url();
        $this->resetValidation();

        Flux::modal('category-form')->show();
    }

    /**
     * Create or update the category being edited. The slug and card color
     * are managed automatically: the slug derives from the title on create
     * (and never changes afterwards, keeping published URLs stable), while
     * the color rotates through the palette.
     */
    public function save(): void
    {
        $validated = $this->validate(
            rules: [
                'title' => ['required', 'string', 'max:255'],
                'description' => ['nullable', 'string', 'max:1000'],
                'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            ],
            attributes: [
                'title' => __('title attribute'),
                'description' => __('description attribute'),
                'image' => __('image attribute'),
            ],
        );

        $existing = $this->editingId !== null
            ? Category::query()->whereKey($this->editingId)->firstOrFail()
            : null;

        $path = $existing?->path;

        if ($this->image !== null) {
            if ($path !== null) {
                Storage::disk('public')->delete($path);
            }

            $path = app(StoreOptimizedImage::class)->handle($this->image, 'categories', 1200);
        }

        $data = [
            'title' => $validated['title'],
            'slug' => $existing?->slug ?? Category::generateUniqueSlug($validated['title']),
            'description' => $this->description !== '' ? $this->description : null,
            'color' => $existing?->color ?? CategoryColor::forIndex(Category::query()->count()),
            'path' => $path,
            'position' => $existing?->position ?? (int) Category::query()->max('position') + 1,
        ];

        $existing !== null ? $existing->update($data) : Category::query()->create($data);

        $this->resetForm();
        unset($this->categories);

        Flux::modal('category-form')->close();
        Flux::toast(variant: 'success', text: __('Category saved.'));
    }

    /**
     * Remove the stored image without touching the rest of the category.
     */
    public function removeImage(Category $category): void
    {
        if ($category->path !== null) {
            Storage::disk('public')->delete($category->path);
        }

        $category->update(['path' => null]);
        unset($this->categories);

        Flux::toast(variant: 'success', text: __('Category image removed.'));
    }

    /**
     * Persist a new order after a row is dragged to another position.
     */
    public function sort(int $id, int $position): void
    {
        $ids = Category::query()->ordered()->pluck('id')
            ->reject(fn (int $categoryId): bool => $categoryId === $id)
            ->values()
            ->all();

        array_splice($ids, $position, 0, [$id]);

        foreach ($ids as $index => $categoryId) {
            Category::query()->whereKey($categoryId)->update(['position' => $index]);
        }

        unset($this->categories);

        Flux::toast(variant: 'success', text: __('Order updated.'));
    }

    /**
     * Toggle whether the category is shown on the public site.
     */
    public function toggleVisibility(Category $category): void
    {
        $category->update(['is_visible' => ! $category->is_visible]);
        unset($this->categories);

        Flux::toast(variant: 'success', text: $category->is_visible
            ? __('Category is now visible.')
            : __('Category is now hidden.'));
    }

    /**
     * Delete the category; its model event cleans up the stored image
     * files for the category and its products.
     */
    public function delete(Category $category): void
    {
        $category->delete();
        unset($this->categories);

        Flux::toast(variant: 'success', text: __('Category deleted.'));
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'title', 'description', 'image', 'existingImageUrl');
        $this->resetValidation();
    }
}; ?>

<section class="mx-auto w-full max-w-6xl">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Categories') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">
            {{ __('Product categories shown on the home page and as public pages. Hidden categories are not reachable on the public site.') }}
        </flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="mb-6 flex justify-end">
        <flux:button variant="primary" icon="plus" wire:click="create" data-test="add-category-button">
            {{ __('Add category') }}
        </flux:button>
    </div>

    @if ($this->categories->isNotEmpty())
        <flux:table>
            <flux:table.columns>
                <flux:table.column></flux:table.column>
                <flux:table.column>{{ __('Category') }}</flux:table.column>
                <flux:table.column>{{ __('Products') }}</flux:table.column>
                <flux:table.column>{{ __('Visible') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows wire:sort="sort">
                @foreach ($this->categories as $category)
                    <flux:table.row :key="$category->id" wire:sort:item="{{ $category->id }}">
                        <flux:table.cell class="w-10">
                            <div wire:sort:handle class="cursor-grab text-zinc-400" aria-label="{{ __('Reorder') }}">
                                <flux:icon.bars-3 class="size-5" />
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="max-w-md">
                            <div class="flex items-center gap-3">
                                @if ($category->url())
                                    <img src="{{ $category->url() }}" alt="" class="size-10 shrink-0 rounded-lg object-cover">
                                @else
                                    <flux:icon.photo class="size-10 shrink-0 rounded-lg bg-zinc-100 p-2 text-zinc-400 dark:bg-zinc-700" />
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate font-medium">{{ $category->title }}</p>
                                    <p class="truncate text-sm text-zinc-500">/{{ $category->slug }}</p>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $category->products_count }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:switch
                                :checked="$category->is_visible"
                                wire:click="toggleVisibility({{ $category->id }})"
                                aria-label="{{ __('Visible') }}"
                            />
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">
                            <flux:button size="sm" icon="pencil-square" wire:click="edit({{ $category->id }})" aria-label="{{ __('Edit') }}" />
                            <flux:button
                                size="sm"
                                variant="danger"
                                icon="trash"
                                wire:click="delete({{ $category->id }})"
                                wire:confirm="{{ __('Delete this category and all its products?') }}"
                                aria-label="{{ __('Delete this category and all its products?') }}"
                            />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @else
        <flux:callout icon="information-circle" variant="secondary">
            <flux:callout.text>{{ __('No categories yet - the category section is hidden on the home page.') }}</flux:callout.text>
        </flux:callout>
    @endif

    <flux:modal name="category-form" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">
                {{ $editingId ? __('Edit category') : __('Add category') }}
            </flux:heading>

            <flux:input wire:model="title" :label="__('Title')" type="text" required />

            <flux:textarea wire:model="description" :label="__('Description')" :description="__('Shown below the title on the category page.')" rows="3" />

            <x-admin.image-field :image="$image" :existing-image-url="$existingImageUrl" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit" data-test="save-category-button">
                    {{ __('Save') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
