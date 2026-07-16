<?php

use App\Models\GalleryCategory;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Galerija')] class extends Component {
    public ?int $editingId = null;

    public string $title = '';

    /**
     * @return Collection<int, GalleryCategory>
     */
    #[Computed]
    public function galleryCategories(): Collection
    {
        return GalleryCategory::query()
            ->withCount('images')
            ->with(['images' => fn ($query) => $query->limit(1)])
            ->ordered()
            ->get();
    }

    /**
     * Open the form modal for a new gallery category.
     */
    public function create(): void
    {
        $this->resetForm();

        Flux::modal('gallery-category-form')->show();
    }

    /**
     * Open the form modal for an existing gallery category.
     */
    public function edit(GalleryCategory $category): void
    {
        $this->editingId = $category->id;
        $this->title = $category->title;
        $this->resetValidation();

        Flux::modal('gallery-category-form')->show();
    }

    /**
     * Create or update the gallery category being edited. The slug derives
     * from the title on create and never changes afterwards, keeping
     * published URLs stable. The public card image is always the category's
     * first photo, so the form only asks for a title.
     */
    public function save(): void
    {
        $validated = $this->validate(
            rules: ['title' => ['required', 'string', 'max:255']],
            attributes: ['title' => __('title attribute')],
        );

        $existing = $this->editingId !== null
            ? GalleryCategory::query()->whereKey($this->editingId)->firstOrFail()
            : null;

        $data = [
            'title' => $validated['title'],
            'slug' => $existing?->slug ?? GalleryCategory::generateUniqueSlug($validated['title']),
            'position' => $existing?->position ?? (int) GalleryCategory::query()->max('position') + 1,
        ];

        if ($existing !== null) {
            $existing->update($data);
        } else {
            $created = GalleryCategory::query()->create($data);

            // Send the admin straight to the upload page so the new
            // category gets its photos right away.
            $this->redirectRoute('gallery-categories.photos', $created, navigate: true);

            return;
        }

        $this->resetForm();
        unset($this->galleryCategories);

        Flux::modal('gallery-category-form')->close();
        Flux::toast(variant: 'success', text: __('Category saved.'));
    }

    /**
     * Persist a new order after a row is dragged to another position.
     */
    public function sort(int $id, int $position): void
    {
        $ids = GalleryCategory::query()->ordered()->pluck('id')
            ->reject(fn (int $categoryId): bool => $categoryId === $id)
            ->values()
            ->all();

        array_splice($ids, $position, 0, [$id]);

        foreach ($ids as $index => $categoryId) {
            GalleryCategory::query()->whereKey($categoryId)->update(['position' => $index]);
        }

        unset($this->galleryCategories);

        Flux::toast(variant: 'success', text: __('Order updated.'));
    }

    /**
     * Toggle whether the category is shown on the public gallery.
     */
    public function toggleVisibility(GalleryCategory $category): void
    {
        $category->update(['is_visible' => ! $category->is_visible]);
        unset($this->galleryCategories);

        Flux::toast(variant: 'success', text: $category->is_visible
            ? __('Category is now visible.')
            : __('Category is now hidden.'));
    }

    /**
     * Delete the category; its model event cleans up the stored photo files.
     */
    public function delete(GalleryCategory $category): void
    {
        $category->delete();
        unset($this->galleryCategories);

        Flux::toast(variant: 'success', text: __('Category deleted.'));
    }

    private function resetForm(): void
    {
        $this->reset('editingId', 'title');
        $this->resetValidation();
    }
}; ?>

<section class="mx-auto w-full max-w-6xl">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Gallery') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">
            {{ __('Photo albums shown on the public Galerija page. Hidden categories are not reachable on the public site.') }}
        </flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="mb-6 flex justify-end">
        <flux:button variant="primary" icon="plus" wire:click="create" data-test="add-gallery-category-button">
            {{ __('Add category') }}
        </flux:button>
    </div>

    @if ($this->galleryCategories->isNotEmpty())
        <flux:table>
            <flux:table.columns>
                <flux:table.column></flux:table.column>
                <flux:table.column>{{ __('Category') }}</flux:table.column>
                <flux:table.column>{{ __('Photos') }}</flux:table.column>
                <flux:table.column>{{ __('Visible') }}</flux:table.column>
                <flux:table.column></flux:table.column>
            </flux:table.columns>

            <flux:table.rows wire:sort="sort">
                @foreach ($this->galleryCategories as $category)
                    <flux:table.row :key="$category->id" wire:sort:item="{{ $category->id }}">
                        <flux:table.cell class="w-10">
                            <div wire:sort:handle class="cursor-grab text-zinc-400" aria-label="{{ __('Reorder') }}">
                                <flux:icon.bars-3 class="size-5" />
                            </div>
                        </flux:table.cell>
                        <flux:table.cell class="max-w-md">
                            <div class="flex items-center gap-3">
                                @if ($category->coverUrl())
                                    <img src="{{ $category->coverUrl() }}" alt="" class="size-10 shrink-0 rounded-lg object-cover">
                                @else
                                    <flux:icon.photo class="size-10 shrink-0 rounded-lg bg-zinc-100 p-2 text-zinc-400 dark:bg-zinc-700" />
                                @endif
                                <div class="min-w-0">
                                    <p class="truncate font-medium">{{ $category->title }}</p>
                                    <p class="truncate text-sm text-zinc-500">/galerija/{{ $category->slug }}</p>
                                </div>
                            </div>
                        </flux:table.cell>
                        <flux:table.cell>{{ $category->images_count }}</flux:table.cell>
                        <flux:table.cell>
                            <flux:switch
                                :checked="$category->is_visible"
                                wire:click="toggleVisibility({{ $category->id }})"
                                aria-label="{{ __('Visible') }}"
                            />
                        </flux:table.cell>
                        <flux:table.cell class="whitespace-nowrap">
                            <flux:button
                                size="sm"
                                icon="photo"
                                :href="route('gallery-categories.photos', $category)"
                                wire:navigate
                                aria-label="{{ __('Manage photos') }}"
                            />
                            <flux:button size="sm" icon="pencil-square" wire:click="edit({{ $category->id }})" aria-label="{{ __('Edit') }}" />
                            <flux:button
                                size="sm"
                                variant="danger"
                                icon="trash"
                                wire:click="delete({{ $category->id }})"
                                wire:confirm="{{ __('Delete this category and all its photos?') }}"
                                aria-label="{{ __('Delete this category and all its photos?') }}"
                            />
                        </flux:table.cell>
                    </flux:table.row>
                @endforeach
            </flux:table.rows>
        </flux:table>
    @else
        <flux:callout icon="information-circle" variant="secondary">
            <flux:callout.text>{{ __('No gallery categories yet - the public Galerija page is empty.') }}</flux:callout.text>
        </flux:callout>
    @endif

    <flux:modal name="gallery-category-form" class="w-full max-w-lg">
        <form wire:submit="save" class="space-y-6">
            <flux:heading size="lg">
                {{ $editingId ? __('Edit category') : __('Add category') }}
            </flux:heading>

            <flux:input wire:model="title" :label="__('Title')" type="text" required
                :description="$editingId ? null : __('After saving you will be taken straight to the photo upload - the first photo becomes the category cover.')" />

            <div class="flex justify-end gap-2">
                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>

                <flux:button variant="primary" type="submit" data-test="save-gallery-category-button">
                    {{ $editingId ? __('Save') : __('Save and add photos') }}
                </flux:button>
            </div>
        </form>
    </flux:modal>
</section>
