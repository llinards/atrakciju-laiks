<?php

use App\Models\GalleryCategory;
use App\Models\GalleryImage;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

new #[Layout('layouts::public')] class extends Component {
    use WithPagination;

    public GalleryCategory $galleryCategory;

    public function mount(GalleryCategory $galleryCategory): void
    {
        abort_unless($galleryCategory->is_visible, 404);
    }

    public function rendering(View $view): void
    {
        $view->title($this->galleryCategory->title);
    }

    /**
     * @return LengthAwarePaginator<int, GalleryImage>
     */
    #[Computed]
    public function photos(): LengthAwarePaginator
    {
        return $this->galleryCategory->images()->paginate(32);
    }

    /**
     * The current page's photos as the lightbox data source.
     *
     * @return list<array{src: string, alt: string, width: int, height: int}>
     */
    #[Computed]
    public function images(): array
    {
        return $this->photos->map(fn (GalleryImage $image): array => [
            'src' => $image->url(),
            'alt' => $this->galleryCategory->title,
            'width' => $image->width,
            'height' => $image->height,
        ])->all();
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
        <a href="{{ route('gallery.index') }}" wire:navigate
            class="inline-flex w-fit items-center gap-2 font-heading text-lg font-semibold text-gray-700 transition-colors hover:text-brand">
            <x-public.icons.arrow-left class="size-5" />
            Atpakaļ
        </a>

        <x-public.section-heading tag="h1" align="left"
            subtitle="Ieskaties mūsu atrakcijās darbībā un izvēlies piemērotāko risinājumu savam pasākumam.">
            {{ $galleryCategory->title }}
        </x-public.section-heading>

        @if ($this->photos->isNotEmpty())
            {{-- Keyed to the page so Alpine re-initializes the lightbox with the new page's photos. --}}
            <x-public.masonry-gallery wire:key="photos-page-{{ $this->photos->currentPage() }}"
                :images="$this->images" />

            <x-public.pagination :paginator="$this->photos" prev-label="Atpakaļ" next-label="Tālāk"
                class="border-t border-gray-200 pt-8" />
        @else
            <p class="py-16 text-center font-heading text-xl font-semibold text-gray-600">
                Šajā galerijā pagaidām nav pievienotu attēlu.
            </p>
        @endif
    </div>
</div>

<script>
    this.$js.scrollToTop = () => {
        window.scrollTo({ top: 0, behavior: 'smooth' })
    }
</script>
