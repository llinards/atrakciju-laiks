<?php

use App\Models\GalleryCategory;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::public')] class extends Component {
    public function rendering(View $view): void
    {
        $view->title('Galerija');

        app(Seo::class)
            ->describe('Ieskaties mūsu atrakcijās darbībā un izvēlies piemērotāko risinājumu savam pasākumam.')
            ->canonical(route('gallery.index'));
    }

    /**
     * @return Collection<int, GalleryCategory>
     */
    #[Computed]
    public function categories(): Collection
    {
        return GalleryCategory::query()
            ->visible()
            ->ordered()
            ->with(['images' => fn ($query) => $query->limit(1)])
            ->get();
    }
};
?>

<div class="px-4 pb-16 pt-8 lg:px-8">
    <div class="mx-auto flex max-w-7xl flex-col gap-8">
        <a href="{{ route('home') }}" wire:navigate
            class="inline-flex w-fit items-center gap-2 font-heading text-lg font-semibold text-gray-700 transition-colors hover:text-brand">
            <x-public.icons.arrow-left class="size-5" />
            Atpakaļ
        </a>

        <x-public.section-heading tag="h1" align="left"
            subtitle="Ieskaties mūsu atrakcijās darbībā un izvēlies piemērotāko risinājumu savam pasākumam.">
            Galerija
        </x-public.section-heading>

        @if ($this->categories->isNotEmpty())
            <div class="grid gap-x-9 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($this->categories as $category)
                    <x-public.gallery-card wire:key="gallery-category-{{ $category->id }}" :title="$category->title"
                        :image="$category->coverUrl()" :href="route('gallery.show', $category)" />
                @endforeach
            </div>
        @else
            <p class="py-16 text-center font-heading text-xl font-semibold text-gray-600">
                Galerijā pagaidām nav pievienotu attēlu.
            </p>
        @endif
    </div>
</div>
