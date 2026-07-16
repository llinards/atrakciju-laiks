<?php

use App\Actions\StoreOptimizedImage;
use App\Models\GalleryCategory;
use App\Models\GalleryImage;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public GalleryCategory $galleryCategory;

    /** @var array<int, TemporaryUploadedFile> */
    public array $uploads = [];

    public function rendering(View $view): void
    {
        $view->title($this->galleryCategory->title);
    }

    /**
     * @return Collection<int, GalleryImage>
     */
    #[Computed]
    public function images(): Collection
    {
        return $this->galleryCategory->images()->get();
    }

    /**
     * Store the staged uploads at the end of the gallery, capturing each
     * photo's final dimensions for the public masonry grid and lightbox.
     */
    public function saveUploads(): void
    {
        $this->validate(
            rules: ['uploads.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120']],
            attributes: ['uploads.*' => __('image attribute')],
        );

        $position = (int) $this->galleryCategory->images()->max('position');

        foreach ($this->uploads as $upload) {
            $path = app(StoreOptimizedImage::class)->handle($upload, 'gallery', GalleryImage::IMAGE_WIDTH);

            $size = getimagesize(Storage::disk('public')->path($path));

            throw_unless(is_array($size), RuntimeException::class, 'Could not read stored image dimensions.');

            $this->galleryCategory->images()->create([
                'path' => $path,
                'width' => $size[0],
                'height' => $size[1],
                'position' => ++$position,
            ]);
        }

        $this->reset('uploads');
        unset($this->images);

        Flux::toast(variant: 'success', text: __('Images added.'));
    }

    /**
     * Remove a staged upload before it is stored.
     */
    public function removeUpload(int $index): void
    {
        unset($this->uploads[$index]);
        $this->uploads = array_values($this->uploads);
    }

    /**
     * Persist a new order after a photo is dragged to another position.
     */
    public function sortImages(int $id, int $position): void
    {
        $ids = $this->galleryCategory->images()
            ->pluck('id')
            ->reject(fn (int $imageId): bool => $imageId === $id)
            ->values()
            ->all();

        array_splice($ids, $position, 0, [$id]);

        foreach ($ids as $index => $imageId) {
            GalleryImage::query()->whereKey($imageId)->where('gallery_category_id', $this->galleryCategory->id)->update(['position' => $index]);
        }

        unset($this->images);

        Flux::toast(variant: 'success', text: __('Order updated.'));
    }

    /**
     * Delete a photo; its model event removes the stored file.
     */
    public function deleteImage(GalleryImage $image): void
    {
        abort_unless($image->gallery_category_id === $this->galleryCategory->id, 404);

        $image->delete();
        unset($this->images);

        Flux::toast(variant: 'success', text: __('Image deleted.'));
    }
}; ?>

<section class="mx-auto w-full max-w-6xl">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ $galleryCategory->title }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">
            {{ __('Photos shown on the public gallery page. Drag to reorder.') }}
        </flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="mb-6">
        <flux:button variant="ghost" icon="arrow-left" :href="route('gallery-categories.edit')" wire:navigate>
            {{ __('Back to gallery') }}
        </flux:button>
    </div>

    <div class="space-y-6">
        @if ($this->images->isNotEmpty())
            <div class="grid grid-cols-2 gap-4 sm:grid-cols-3" wire:sort="sortImages">
                @foreach ($this->images as $image)
                    <div wire:key="photo-{{ $image->id }}" wire:sort:item="{{ $image->id }}"
                        class="group relative overflow-clip rounded-lg border border-zinc-200 dark:border-white/10">
                        <img src="{{ $image->url() }}" alt="" class="aspect-[5/4] w-full object-cover">

                        <div wire:sort:handle
                            class="absolute left-2 top-2 cursor-grab rounded-md bg-white/90 p-1.5 text-zinc-500 shadow-xs"
                            aria-label="{{ __('Reorder') }}">
                            <flux:icon.bars-3 class="size-4" />
                        </div>

                        <div class="absolute right-2 top-2">
                            <flux:button size="sm" variant="danger" icon="trash"
                                wire:click="deleteImage({{ $image->id }})"
                                wire:confirm="{{ __('Delete this image?') }}" aria-label="{{ __('Delete this image?') }}" />
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <flux:callout icon="information-circle" variant="secondary">
                <flux:callout.text>{{ __('No photos yet - upload the first ones below.') }}</flux:callout.text>
            </flux:callout>
        @endif

        @if ($uploads !== [])
            <div class="space-y-2">
                @foreach ($uploads as $index => $upload)
                    <flux:file-item :image="$upload->temporaryUrl()" :heading="$upload->getClientOriginalName()"
                        wire:key="upload-{{ $index }}">
                        <flux:file-item.remove wire:click="removeUpload({{ $index }})" />
                    </flux:file-item>
                @endforeach
            </div>

            <flux:button variant="primary" icon="plus" wire:click="saveUploads" data-test="save-uploads-button">
                {{ __('Add images') }}
            </flux:button>
        @endif

        <flux:file-upload wire:model="uploads" multiple>
            <flux:file-upload.dropzone
                :heading="__('Drop files here or click to browse')"
                :text="__('JPG, PNG, WEBP up to 5MB - optimized automatically')"
            />
        </flux:file-upload>

        <flux:error name="uploads.*" />
    </div>
</section>
