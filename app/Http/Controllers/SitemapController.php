<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\GalleryCategory;
use App\Models\Product;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * Render the XML sitemap: static pages plus every visible category,
     * product (at its canonical category URL), and gallery category.
     */
    public function __invoke(): Response
    {
        $urls = collect([
            ['loc' => route('home')],
            ['loc' => route('gallery.index')],
            ['loc' => route('sale.index')],
            ['loc' => route('contact')],
        ]);

        $urls = $urls
            ->concat(Category::query()->visible()->get()->map(fn (Category $category): array => [
                'loc' => route('category.show', $category),
                'lastmod' => $category->updated_at?->toAtomString(),
            ]))
            ->concat(
                Product::query()
                    ->visible()
                    ->with('category')
                    ->get()
                    ->filter(fn (Product $product): bool => $product->category->is_visible)
                    ->map(fn (Product $product): array => [
                        'loc' => route('product.show', [$product->category, $product]),
                        'lastmod' => $product->updated_at?->toAtomString(),
                    ]),
            )
            ->concat(GalleryCategory::query()->visible()->get()->map(fn (GalleryCategory $galleryCategory): array => [
                'loc' => route('gallery.show', $galleryCategory),
                'lastmod' => $galleryCategory->updated_at?->toAtomString(),
            ]));

        return response()
            ->view('sitemap', ['urls' => $urls])
            ->header('Content-Type', 'application/xml');
    }
}
