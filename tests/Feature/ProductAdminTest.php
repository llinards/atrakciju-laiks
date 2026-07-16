<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('guests cannot view the products admin page', function () {
    $this->get(route('products.index'))->assertRedirect(route('login'));
});

test('products admin page is displayed', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('products.index'))->assertOk();
});

test('the list links to the create and edit pages', function () {
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create();

    $this->get(route('products.index'))
        ->assertSee(route('products.create'))
        ->assertSee(route('products.edit', $product));
});

test('a product can be deleted and its image file is removed', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $path = UploadedFile::fake()->image('produkts.jpg')->store('products', 'public');
    $product = Product::factory()->create(['path' => $path]);

    Livewire::test('pages::admin.products')
        ->call('delete', $product->id);

    expect(Product::query()->count())->toBe(0);
    Storage::disk('public')->assertMissing($path);
});

test('deleting a product also removes its gallery image files', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create();
    $galleryPath = UploadedFile::fake()->image('galerija.jpg')->store('products/gallery', 'public');
    ProductImage::factory()->for($product)->create(['path' => $galleryPath]);

    Livewire::test('pages::admin.products')
        ->call('delete', $product->id);

    expect(ProductImage::query()->count())->toBe(0);
    Storage::disk('public')->assertMissing($galleryPath);
});

test('visibility can be toggled', function () {
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create();

    Livewire::test('pages::admin.products')
        ->call('toggleVisibility', $product->id);

    expect($product->refresh()->is_visible)->toBeFalse();
});

test('products can be reordered within their category', function () {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();
    $other = Category::factory()->create();

    $first = Product::factory()->for($category)->create(['position' => 0]);
    $second = Product::factory()->for($category)->create(['position' => 1]);
    $third = Product::factory()->for($category)->create(['position' => 2]);
    $foreign = Product::factory()->for($other)->create(['position' => 0]);

    Livewire::test('pages::admin.products')
        ->call('sort', $third->id, 0);

    expect($third->refresh()->position)->toBe(0)
        ->and($first->refresh()->position)->toBe(1)
        ->and($second->refresh()->position)->toBe(2)
        ->and($foreign->refresh()->position)->toBe(0);
});

test('reordering works while the list is filtered by category', function () {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();

    $first = Product::factory()->for($category)->create(['position' => 0]);
    $second = Product::factory()->for($category)->create(['position' => 1]);

    Livewire::test('pages::admin.products')
        ->set('categoryFilter', (string) $category->id)
        ->call('sort', $second->id, 0);

    expect($second->refresh()->position)->toBe(0)
        ->and($first->refresh()->position)->toBe(1);
});

test('the list can be filtered by category', function () {
    $this->actingAs(User::factory()->create());

    $teltis = Category::factory()->create();
    $nojumes = Category::factory()->create();

    $telts = Product::factory()->for($teltis)->create(['name' => 'Liela telts']);
    $nojume = Product::factory()->for($nojumes)->create(['name' => 'Balta nojume']);

    Livewire::test('pages::admin.products')
        ->assertSee($telts->name)
        ->assertSee($nojume->name)
        ->set('categoryFilter', (string) $teltis->id)
        ->assertSee($telts->name)
        ->assertDontSee($nojume->name);
});
