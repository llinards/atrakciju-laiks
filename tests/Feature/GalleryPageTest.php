<?php

use App\Models\Category;
use App\Models\GalleryCategory;
use App\Models\GalleryImage;
use Livewire\Livewire;

test('gallery index renders heading, subtitle and visible categories', function () {
    $visible = GalleryCategory::factory()->create(['title' => 'Lielās piepūšamās atrakcijas']);
    GalleryCategory::factory()->hidden()->create(['title' => 'Slēptā kategorija']);

    $response = $this->get(route('gallery.index'));

    $response->assertSuccessful();
    $response->assertSeeInOrder([
        'Galerija',
        'Ieskaties mūsu atrakcijās darbībā un izvēlies piemērotāko risinājumu savam pasākumam.',
        'Lielās piepūšamās atrakcijas',
        'Skatīt',
    ]);
    $response->assertSee(route('gallery.show', $visible), escape: false);
    $response->assertDontSee('Slēptā kategorija');
});

test('gallery index shows an empty state without categories', function () {
    $this->get(route('gallery.index'))
        ->assertSuccessful()
        ->assertSee('Galerijā pagaidām nav pievienotu attēlu.');
});

test('the category card uses the first photo as its cover', function () {
    $category = GalleryCategory::factory()->create();
    GalleryImage::factory()->for($category)->create(['path' => 'gallery/pirmais-foto.webp', 'position' => 0]);
    GalleryImage::factory()->for($category)->create(['path' => 'gallery/otrais-foto.webp', 'position' => 1]);

    $this->get(route('gallery.index'))
        ->assertSuccessful()
        ->assertSee('gallery/pirmais-foto.webp')
        ->assertDontSee('gallery/otrais-foto.webp');
});

test('gallery category page renders title, subtitle and photos', function () {
    $category = GalleryCategory::factory()->create(['title' => 'Teltis un nojumes']);
    GalleryImage::factory()->for($category)->create(['path' => 'gallery/telts-foto.webp']);

    $response = $this->get(route('gallery.show', $category));

    $response->assertSuccessful();
    $response->assertSeeInOrder([
        'Atpakaļ',
        'Teltis un nojumes',
        'Ieskaties mūsu atrakcijās darbībā un izvēlies piemērotāko risinājumu savam pasākumam.',
    ]);
    $response->assertSee('gallery/telts-foto.webp');
});

test('gallery category page shows an empty state without photos', function () {
    $category = GalleryCategory::factory()->create();

    $this->get(route('gallery.show', $category))
        ->assertSuccessful()
        ->assertSee('Šajā galerijā pagaidām nav pievienotu attēlu.');
});

test('hidden gallery category returns 404', function () {
    $category = GalleryCategory::factory()->hidden()->create();

    $this->get(route('gallery.show', $category))->assertNotFound();
});

test('unknown gallery category slug returns 404', function () {
    $this->get('/galerija/neeksiste-tada-galerija')->assertNotFound();
});

test('photos are paginated by 32 with a page counter', function () {
    $category = GalleryCategory::factory()->create();

    GalleryImage::factory()->for($category)->count(35)->sequence(
        fn ($sequence) => ['path' => 'gallery/foto-'.($sequence->index + 1).'.webp', 'position' => $sequence->index],
    )->create();

    $response = $this->get(route('gallery.show', $category));

    $response->assertSuccessful();
    $response->assertSee('gallery/foto-32.webp');
    $response->assertDontSee('gallery/foto-33.webp');
    $response->assertSee('1/2');
    $response->assertSee('Tālāk');

    Livewire::test('pages::public.gallery-category', ['galleryCategory' => $category])
        ->call('nextPage')
        ->assertSee('gallery/foto-33.webp')
        ->assertDontSee('gallery/foto-32.webp');
});

test('a category slug cannot shadow the gallery page', function () {
    expect(Category::generateUniqueSlug('Galerija'))->toBe('galerija-2');

    Category::factory()->create(['slug' => 'galerija-2']);

    $this->get(route('gallery.index'))->assertSuccessful();
});
