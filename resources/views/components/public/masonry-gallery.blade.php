@props([
    'images' => [],
])

<div {{ $attributes->merge(['class' => 'columns-1 gap-5 sm:columns-2 lg:columns-3']) }}
    x-data="lightboxGallery(@js($images))">
    @foreach ($images as $index => $image)
        <button type="button" @click="open({{ $index }})" aria-label="Skatīt attēlu pilnekrānā"
            class="mb-5 block w-full cursor-zoom-in break-inside-avoid overflow-clip rounded-2xl bg-gray-100">
            <img src="{{ $image['src'] }}" alt="{{ $image['alt'] }}" width="{{ $image['width'] }}"
                height="{{ $image['height'] }}" loading="lazy" class="h-auto w-full">
        </button>
    @endforeach
</div>
