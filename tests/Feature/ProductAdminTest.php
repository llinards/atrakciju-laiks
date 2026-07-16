<?php

use App\Enums\ProductSize;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('guests cannot view the products admin page', function () {
    $this->get(route('products.edit'))->assertRedirect(route('login'));
});

test('products admin page is displayed', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('products.edit'))->assertOk();
});

test('a product can be created', function () {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();

    Livewire::test('pages::admin.products')
        ->call('create')
        ->set('categoryId', (string) $category->id)
        ->set('name', 'Piepūšamā pils')
        ->set('price', '130')
        ->call('save')
        ->assertHasNoErrors();

    expect(Product::query()->sole())
        ->category_id->toBe($category->id)
        ->name->toBe('Piepūšamā pils')
        ->is_visible->toBeTrue()
        ->discount_price->toBeNull()
        ->size->toBeNull()
        ->is_new->toBeFalse();
});

test('a product can be marked as new and unmarked again', function () {
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create();

    Livewire::test('pages::admin.products')
        ->call('edit', $product->id)
        ->assertSet('isNew', false)
        ->set('isNew', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($product->refresh()->is_new)->toBeTrue();

    Livewire::test('pages::admin.products')
        ->call('edit', $product->id)
        ->assertSet('isNew', true)
        ->set('isNew', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($product->refresh()->is_new)->toBeFalse();
});

test('a product can be updated', function () {
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create();

    Livewire::test('pages::admin.products')
        ->call('edit', $product->id)
        ->assertSet('name', $product->name)
        ->set('name', 'Atjaunināts produkts')
        ->set('price', '99.50')
        ->call('save')
        ->assertHasNoErrors();

    expect($product->refresh())
        ->name->toBe('Atjaunināts produkts')
        ->price->toBe('99.50');

    expect(Product::query()->count())->toBe(1);
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

test('the discount price must be lower than the standard price', function (string $discountPrice) {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();

    Livewire::test('pages::admin.products')
        ->call('create')
        ->set('categoryId', (string) $category->id)
        ->set('name', 'Produkts')
        ->set('price', '100')
        ->set('discountPrice', $discountPrice)
        ->call('save')
        ->assertHasErrors(['discountPrice']);
})->with(['equal' => '100', 'higher' => '110']);

test('the discount price is optional', function () {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();

    Livewire::test('pages::admin.products')
        ->call('create')
        ->set('categoryId', (string) $category->id)
        ->set('name', 'Produkts')
        ->set('price', '100')
        ->set('discountPrice', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(Product::query()->sole()->discount_price)->toBeNull();
});

test('a discounted product shows the discount price with the standard price struck through', function () {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();

    Livewire::test('pages::admin.products')
        ->call('create')
        ->set('categoryId', (string) $category->id)
        ->set('name', 'Smurfi')
        ->set('price', '160')
        ->set('discountPrice', '130')
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::query()->sole();

    expect($product->formattedPrice())->toBe('130€')
        ->and($product->formattedOriginalPrice())->toBe('160€')
        ->and($product->discountPercent())->toBe(19);
});

test('the size is optional and stored as an enum', function () {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();

    Livewire::test('pages::admin.products')
        ->call('create')
        ->set('categoryId', (string) $category->id)
        ->set('name', 'Bez izmēra')
        ->set('price', '50')
        ->set('size', '')
        ->call('save')
        ->assertHasNoErrors();

    Livewire::test('pages::admin.products')
        ->call('create')
        ->set('categoryId', (string) $category->id)
        ->set('name', 'Ar izmēru')
        ->set('price', '60')
        ->set('size', 'large')
        ->call('save')
        ->assertHasNoErrors();

    expect(Product::query()->where('name', 'Bez izmēra')->sole()->size)->toBeNull()
        ->and(Product::query()->where('name', 'Ar izmēru')->sole()->size)->toBe(ProductSize::Large);
});

test('an invalid size is rejected', function () {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();

    Livewire::test('pages::admin.products')
        ->call('create')
        ->set('categoryId', (string) $category->id)
        ->set('name', 'Produkts')
        ->set('price', '50')
        ->set('size', 'nepareizs')
        ->call('save')
        ->assertHasErrors(['size']);
});

test('a product image is optimized and stored as webp', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();

    Livewire::test('pages::admin.products')
        ->call('create')
        ->set('categoryId', (string) $category->id)
        ->set('name', 'Ar attēlu')
        ->set('price', '80')
        ->set('image', UploadedFile::fake()->image('foto.jpg', 2000, 1500))
        ->call('save')
        ->assertHasNoErrors();

    $product = Product::query()->sole();

    expect($product->path)->toEndWith('.webp');
    Storage::disk('public')->assertExists($product->path);

    [$width, $height] = getimagesize(Storage::disk('public')->path($product->path));
    expect($width)->toBe(1200)->and($height)->toBe(960);
});

test('replacing the product image deletes the old file', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $oldPath = UploadedFile::fake()->image('vecais.jpg')->store('products', 'public');
    $product = Product::factory()->create(['path' => $oldPath]);

    Livewire::test('pages::admin.products')
        ->call('edit', $product->id)
        ->set('image', UploadedFile::fake()->image('jaunais.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($product->refresh()->path);
});

test('category, name and price are required', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.products')
        ->set('categoryId', '')
        ->set('name', '')
        ->set('price', '')
        ->call('save')
        ->assertHasErrors(['categoryId', 'name', 'price']);
});

test('the price must be numeric', function () {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();

    Livewire::test('pages::admin.products')
        ->call('create')
        ->set('categoryId', (string) $category->id)
        ->set('name', 'Produkts')
        ->set('price', 'simts')
        ->call('save')
        ->assertHasErrors(['price']);
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
