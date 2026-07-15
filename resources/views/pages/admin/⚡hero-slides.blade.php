<?php

use App\Models\HeroSlide;
use Flux\Flux;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Hero images')] class extends Component {
    use WithFileUploads;

    /** @var array<int, \Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public array $images = [];

    /**
     * @return Collection<int, HeroSlide>
     */
    #[Computed]
    public function slides(): Collection
    {
        return HeroSlide::query()->ordered()->get();
    }

    /**
     * Store the newly uploaded hero images.
     */
    public function save(): void
    {
        $available = HeroSlide::MAX_SLIDES - HeroSlide::query()->count();

        $this->validate(
            rules: [
                'images' => ['required', 'array', 'min:1', "max:{$available}"],
                'images.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:400'],
            ],
            messages: [
                'images.max' => __('You can have at most :max hero images in total.', ['max' => HeroSlide::MAX_SLIDES]),
            ],
            attributes: [
                'images' => __('images'),
                'images.*' => __('image'),
            ],
        );

        $position = (int) HeroSlide::query()->max('position');

        foreach ($this->images as $image) {
            HeroSlide::query()->create([
                'path' => $image->store('hero-slides', 'public'),
                'position' => ++$position,
            ]);
        }

        $this->images = [];
        unset($this->slides);

        Flux::toast(variant: 'success', text: __('Hero images updated.'));
    }

    /**
     * Remove an uploaded file from the pending selection.
     */
    public function removeImage(int $index): void
    {
        $this->images[$index]->delete();
        unset($this->images[$index]);
        $this->images = array_values($this->images);
    }

    /**
     * Delete a stored slide while always keeping at least one.
     */
    public function deleteSlide(HeroSlide $slide): void
    {
        if (HeroSlide::query()->count() <= 1) {
            Flux::toast(variant: 'danger', text: __('At least one hero image is required.'));

            return;
        }

        Storage::disk('public')->delete($slide->path);
        $slide->delete();
        unset($this->slides);

        Flux::toast(variant: 'success', text: __('Hero image deleted.'));
    }
}; ?>

<section class="mx-auto w-full max-w-2xl">
    <div class="relative mb-6 w-full">
        <flux:heading size="xl" level="1">{{ __('Hero images') }}</flux:heading>
        <flux:subheading size="lg" class="mb-6">
            {{ __('Slider images at the top of the home page (up to :max)', ['max' => \App\Models\HeroSlide::MAX_SLIDES]) }}
        </flux:subheading>
        <flux:separator variant="subtle" />
    </div>

    <div class="flex flex-col gap-2">
        @forelse ($this->slides as $slide)
            <flux:file-item :heading="basename($slide->path)" :image="$slide->url()"
                :text="__('Position :position', ['position' => $loop->iteration])">
                <x-slot name="actions">
                    <flux:file-item.remove wire:click="deleteSlide({{ $slide->id }})"
                        wire:confirm="{{ __('Delete this hero image?') }}"
                        aria-label="{{ __('Delete this hero image?') }}" />
                </x-slot>
            </flux:file-item>
        @empty
            <flux:callout icon="information-circle" variant="secondary">
                <flux:callout.text>
                    {{ __('No uploaded images yet - the built-in default images are shown on the home page.') }}
                </flux:callout.text>
            </flux:callout>
        @endforelse
    </div>

    @if ($this->slides->count() < \App\Models\HeroSlide::MAX_SLIDES)
        <form wire:submit="save" class="mt-8 space-y-4">
            <flux:file-upload wire:model="images" multiple :label="__('Add images')">
                <flux:file-upload.dropzone :heading="__('Drop files here or click to browse')"
                    :text="__('JPG, PNG, WEBP up to 400KB')" />
            </flux:file-upload>

            @if ($images)
                <div class="flex flex-col gap-2">
                    @foreach ($images as $index => $image)
                        <flux:file-item :heading="$image->getClientOriginalName()" :image="$image->temporaryUrl()"
                            :size="$image->getSize()">
                            <x-slot name="actions">
                                <flux:file-item.remove wire:click="removeImage({{ $index }})"
                                    aria-label="{{ __('Remove file') }}" />
                            </x-slot>
                        </flux:file-item>
                    @endforeach
                </div>

                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" data-test="save-hero-images-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>
            @endif
        </form>
    @else
        <flux:callout icon="information-circle" variant="secondary" class="mt-8">
            <flux:callout.text>{{ __('Maximum number of hero images reached. Delete one to upload a new image.') }}
            </flux:callout.text>
        </flux:callout>
    @endif
</section>
