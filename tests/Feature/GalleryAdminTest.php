<?php

use App\Models\GalleryCategory;
use App\Models\GalleryImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('guests cannot view the gallery admin pages', function () {
    $category = GalleryCategory::factory()->create();

    $this->get(route('gallery-categories.edit'))->assertRedirect(route('login'));
    $this->get(route('gallery-categories.photos', $category))->assertRedirect(route('login'));
});

test('the gallery admin pages are displayed', function () {
    $this->actingAs(User::factory()->create());

    $category = GalleryCategory::factory()->create();

    $this->get(route('gallery-categories.edit'))->assertOk();
    $this->get(route('gallery-categories.photos', $category))->assertOk();
});

test('creating a gallery category redirects straight to its photo upload', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.gallery-categories')
        ->call('create')
        ->set('title', 'Lielās piepūšamās atrakcijas')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('gallery-categories.photos', GalleryCategory::query()->sole()));

    expect(GalleryCategory::query()->sole())
        ->title->toBe('Lielās piepūšamās atrakcijas')
        ->slug->toBe('lielas-piepusamas-atrakcijas')
        ->is_visible->toBeTrue();
});

test('the slug is not changed when editing the title', function () {
    $this->actingAs(User::factory()->create());

    $category = GalleryCategory::factory()->create();
    $originalSlug = $category->slug;

    Livewire::test('pages::admin.gallery-categories')
        ->call('edit', $category->id)
        ->assertSet('title', $category->title)
        ->set('title', 'Jauns nosaukums')
        ->call('save')
        ->assertHasNoErrors();

    expect($category->refresh())
        ->title->toBe('Jauns nosaukums')
        ->slug->toBe($originalSlug);
});

test('a duplicate title gets a suffixed slug', function () {
    $this->actingAs(User::factory()->create());

    GalleryCategory::factory()->create(['slug' => 'teltis']);

    Livewire::test('pages::admin.gallery-categories')
        ->call('create')
        ->set('title', 'Teltis')
        ->call('save')
        ->assertHasNoErrors();

    expect(GalleryCategory::query()->where('slug', 'teltis-2')->exists())->toBeTrue();
});

test('deleting a gallery category deletes its photos and their files', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $photoPath = UploadedFile::fake()->image('foto.jpg')->store('gallery', 'public');

    $category = GalleryCategory::factory()->create();
    GalleryImage::factory()->for($category)->create(['path' => $photoPath]);
    GalleryImage::factory()->for($category)->create();

    Livewire::test('pages::admin.gallery-categories')
        ->call('delete', $category->id);

    expect(GalleryCategory::query()->count())->toBe(0)
        ->and(GalleryImage::query()->count())->toBe(0);

    Storage::disk('public')->assertMissing($photoPath);
});

test('the title is required', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.gallery-categories')
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title']);
});

test('visibility can be toggled', function () {
    $this->actingAs(User::factory()->create());

    $category = GalleryCategory::factory()->create();

    Livewire::test('pages::admin.gallery-categories')
        ->call('toggleVisibility', $category->id);

    expect($category->refresh()->is_visible)->toBeFalse();
});

test('gallery categories can be reordered', function () {
    $this->actingAs(User::factory()->create());

    $first = GalleryCategory::factory()->create(['position' => 0]);
    $second = GalleryCategory::factory()->create(['position' => 1]);
    $third = GalleryCategory::factory()->create(['position' => 2]);

    Livewire::test('pages::admin.gallery-categories')
        ->call('sort', $third->id, 0);

    expect($third->refresh()->position)->toBe(0)
        ->and($first->refresh()->position)->toBe(1)
        ->and($second->refresh()->position)->toBe(2);
});

test('a new gallery category is placed last', function () {
    $this->actingAs(User::factory()->create());

    GalleryCategory::factory()->create(['position' => 5]);

    Livewire::test('pages::admin.gallery-categories')
        ->call('create')
        ->set('title', 'Jaunā kategorija')
        ->call('save');

    expect(GalleryCategory::query()->where('slug', 'jauna-kategorija')->sole()->position)->toBe(6);
});

test('photo uploads are optimized, keep their aspect ratio and append in order', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $category = GalleryCategory::factory()->create();
    GalleryImage::factory()->for($category)->create(['position' => 0]);

    Livewire::test('pages::admin.gallery-photos', ['galleryCategory' => $category])
        ->set('uploads', [
            UploadedFile::fake()->image('pirmais.jpg', 2000, 1500),
            UploadedFile::fake()->image('otrais.jpg', 1500, 2000),
        ])
        ->call('saveUploads')
        ->assertHasNoErrors();

    $images = $category->images()->get();

    expect($images)->toHaveCount(3)
        ->and($images->pluck('position')->all())->toBe([0, 1, 2])
        ->and($images[1]->path)->toStartWith('gallery/')
        ->and($images[1]->path)->toEndWith('.webp')
        ->and($images[1]->width)->toBe(1600)
        ->and($images[1]->height)->toBe(1200)
        ->and($images[2]->width)->toBe(1500)
        ->and($images[2]->height)->toBe(2000);

    Storage::disk('public')->assertExists($images->last()->path);
});

test('photos can be reordered', function () {
    $this->actingAs(User::factory()->create());

    $category = GalleryCategory::factory()->create();

    $first = GalleryImage::factory()->for($category)->create(['position' => 0]);
    $second = GalleryImage::factory()->for($category)->create(['position' => 1]);
    $third = GalleryImage::factory()->for($category)->create(['position' => 2]);

    Livewire::test('pages::admin.gallery-photos', ['galleryCategory' => $category])
        ->call('sortImages', $third->id, 0);

    expect($third->refresh()->position)->toBe(0)
        ->and($first->refresh()->position)->toBe(1)
        ->and($second->refresh()->position)->toBe(2);
});

test('a photo can be deleted and its file is removed', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $category = GalleryCategory::factory()->create();
    $path = UploadedFile::fake()->image('foto.jpg')->store('gallery', 'public');
    $image = GalleryImage::factory()->for($category)->create(['path' => $path]);

    Livewire::test('pages::admin.gallery-photos', ['galleryCategory' => $category])
        ->call('deleteImage', $image->id);

    expect(GalleryImage::query()->count())->toBe(0);
    Storage::disk('public')->assertMissing($path);
});

test('another category\'s photo cannot be deleted', function () {
    $this->actingAs(User::factory()->create());

    $category = GalleryCategory::factory()->create();
    $foreign = GalleryImage::factory()->create();

    Livewire::test('pages::admin.gallery-photos', ['galleryCategory' => $category])
        ->call('deleteImage', $foreign->id)
        ->assertStatus(404);

    expect(GalleryImage::query()->count())->toBe(1);
});
