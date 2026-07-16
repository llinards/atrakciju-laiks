{{-- Shared admin field for a single optimized image upload; binds the component's `image`
     property. Pass `remove-action` (a component method name) to let the stored image be
     removed, not just replaced. --}}
@props([
    'image' => null,
    'existingImageUrl' => null,
    'removeAction' => null,
])

<div class="space-y-2">
    @if ($image)
        <flux:file-item :heading="$image->getClientOriginalName()" :image="$image->temporaryUrl()" :size="$image->getSize()">
            <x-slot name="actions">
                <flux:file-item.remove wire:click="$set('image', null)" aria-label="{{ __('Remove file') }}" />
            </x-slot>
        </flux:file-item>
    @elseif ($existingImageUrl)
        <flux:file-item :heading="__('Current image')" :image="$existingImageUrl" :text="__('Upload a new file to replace it.')">
            @if ($removeAction)
                <x-slot name="actions">
                    <flux:file-item.remove wire:click="{{ $removeAction }}" wire:confirm="{{ __('Remove this image?') }}"
                        aria-label="{{ __('Remove image') }}" />
                </x-slot>
            @endif
        </flux:file-item>
    @endif

    <flux:file-upload wire:model="image" :label="__('Image')">
        <flux:file-upload.dropzone :heading="__('Drop files here or click to browse')"
            :text="__('JPG, PNG, WEBP up to 5MB - optimized automatically')" />
    </flux:file-upload>
</div>
