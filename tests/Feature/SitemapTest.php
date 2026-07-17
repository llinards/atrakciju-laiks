<?php

use App\Models\Category;
use App\Models\GalleryCategory;
use App\Models\Product;

test('sitemap lists static pages and visible content', function () {
    $category = Category::factory()->create(['slug' => 'teltis']);
    $product = Product::factory()->for($category)->create();
    $galleryCategory = GalleryCategory::factory()->create(['slug' => 'balles']);

    $response = $this->get(route('sitemap'));

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'application/xml');

    $response->assertSee('<loc>'.route('home').'</loc>', escape: false);
    $response->assertSee('<loc>'.route('gallery.index').'</loc>', escape: false);
    $response->assertSee('<loc>'.route('sale.index').'</loc>', escape: false);
    $response->assertSee('<loc>'.route('contact').'</loc>', escape: false);
    $response->assertSee('<loc>'.route('category.show', $category).'</loc>', escape: false);
    $response->assertSee('<loc>'.route('product.show', [$category, $product]).'</loc>', escape: false);
    $response->assertSee('<loc>'.route('gallery.show', $galleryCategory).'</loc>', escape: false);
});

test('sitemap excludes hidden content', function () {
    $hiddenCategory = Category::factory()->hidden()->create(['slug' => 'slepta']);
    $hiddenProduct = Product::factory()->hidden()->for(Category::factory())->create();
    $productInHiddenCategory = Product::factory()->for($hiddenCategory)->create();
    $hiddenGalleryCategory = GalleryCategory::factory()->hidden()->create(['slug' => 'slepta-galerija']);

    $response = $this->get(route('sitemap'));

    $response->assertSuccessful();
    $response->assertDontSee(route('category.show', $hiddenCategory), escape: false);
    $response->assertDontSee($hiddenProduct->slug, escape: false);
    $response->assertDontSee($productInHiddenCategory->slug, escape: false);
    $response->assertDontSee(route('gallery.show', $hiddenGalleryCategory), escape: false);
});

test('sale products appear only at their canonical category URL', function () {
    $category = Category::factory()->create(['slug' => 'atrakcijas']);
    $product = Product::factory()->for($category)->forSale()->create();

    $response = $this->get(route('sitemap'));

    $response->assertSee('<loc>'.route('product.show', [$category, $product]).'</loc>', escape: false);
    $response->assertDontSee(route('sale.show', $product), escape: false);
});
