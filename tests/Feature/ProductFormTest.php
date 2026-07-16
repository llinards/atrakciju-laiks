<?php

use App\Enums\ProductSize;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('guests cannot view the product form pages', function () {
    $product = Product::factory()->create();

    $this->get(route('products.create'))->assertRedirect(route('login'));
    $this->get(route('products.edit', $product))->assertRedirect(route('login'));
});

test('the create and edit pages are displayed', function () {
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create();

    $this->get(route('products.create'))->assertOk();
    $this->get(route('products.edit', $product))->assertOk()->assertSee($product->name);
});

test('a product can be created and redirects to its edit page', function () {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();

    Livewire::test('pages::admin.product-form')
        ->set('categoryId', (string) $category->id)
        ->set('name', 'Piepūšamā pils')
        ->set('price', '130')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('products.edit', Product::query()->sole()));

    expect(Product::query()->sole())
        ->category_id->toBe($category->id)
        ->name->toBe('Piepūšamā pils')
        ->slug->toBe('piepusama-pils')
        ->is_visible->toBeTrue()
        ->discount_price->toBeNull()
        ->size->toBeNull()
        ->is_new->toBeFalse()
        ->is_for_sale->toBeFalse()
        ->sale_price->toBeNull()
        ->description->toBeNull()
        ->specs->toBeNull()
        ->rental_prices->toBeNull()
        ->included_items->toBeNull()
        ->rental_terms->toBeNull();
});

test('detail fields are saved and empty repeater rows are filtered', function () {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();

    Livewire::test('pages::admin.product-form')
        ->set('categoryId', (string) $category->id)
        ->set('name', 'Telts')
        ->set('price', '70')
        ->set('description', '<p>Rekomendējama dažāda veida pasākumiem.</p><ul><li>Bērniem no 3 gadu vecuma</li></ul>')
        ->set('rentalTerms', '<p>Viena nomas diena ir no plkst. 9:00 līdz 20:00.</p>')
        ->set('specs', [
            ['label' => 'Izmēri', 'value' => '16 m x 16 m'],
            ['label' => '', 'value' => ''],
        ])
        ->set('rentalPrices', [
            ['label' => 'Pirmdiena–ceturtdiena', 'value' => '70€'],
        ])
        ->set('includedItems', ['enkurstieņi.', ''])
        ->call('save')
        ->assertHasNoErrors();

    expect(Product::query()->sole())
        ->description->toBe('<p>Rekomendējama dažāda veida pasākumiem.</p><ul><li>Bērniem no 3 gadu vecuma</li></ul>')
        ->rental_terms->toBe('<p>Viena nomas diena ir no plkst. 9:00 līdz 20:00.</p>')
        ->specs->toBe(['Izmēri' => '16 m x 16 m'])
        ->rental_prices->toBe(['Pirmdiena–ceturtdiena' => '70€'])
        ->included_items->toBe(['enkurstieņi.']);
});

test('an empty rich text editor value is stored as null', function () {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();

    Livewire::test('pages::admin.product-form')
        ->set('categoryId', (string) $category->id)
        ->set('name', 'Produkts')
        ->set('price', '50')
        ->set('description', '<p></p>')
        ->call('save')
        ->assertHasNoErrors();

    expect(Product::query()->sole()->description)->toBeNull();
});

test('a spec value without a label is rejected', function () {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();

    Livewire::test('pages::admin.product-form')
        ->set('categoryId', (string) $category->id)
        ->set('name', 'Produkts')
        ->set('price', '50')
        ->set('specs', [['label' => '', 'value' => '9,5 m']])
        ->call('save')
        ->assertHasErrors(['specs.0.label']);
});

test('the unsaved changes flag follows edits and clears on save', function () {
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create();

    Livewire::test('pages::admin.product-form', ['product' => $product])
        ->assertSet('hasUnsavedChanges', false)
        ->set('name', 'Jauns nosaukums')
        ->assertSet('hasUnsavedChanges', true)
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('hasUnsavedChanges', false)
        ->call('addRow', 'specs')
        ->assertSet('hasUnsavedChanges', true);
});

test('gallery uploads do not mark the form as unsaved', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create();

    Livewire::test('pages::admin.product-form', ['product' => $product])
        ->set('galleryUploads', [UploadedFile::fake()->image('foto.jpg')])
        ->assertSet('hasUnsavedChanges', false);
});

test('repeater rows can be added and removed', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.product-form')
        ->call('addRow', 'specs')
        ->assertSet('specs', [['label' => '', 'value' => ''], ['label' => '', 'value' => '']])
        ->call('removeRow', 'specs', 0)
        ->assertSet('specs', [['label' => '', 'value' => '']])
        ->call('addRow', 'includedItems')
        ->assertSet('includedItems', ['', '']);
});

test('the edit page hydrates all fields from the product', function () {
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create([
        'name' => 'Krāsaina zvaigzne',
        'price' => 70,
        'size' => ProductSize::Large,
        'is_new' => true,
        'is_for_sale' => true,
        'sale_price' => 450,
        'description' => "Pirmā rindkopa.\n\nOtrā rindkopa.",
        'specs' => ['Izmēri' => '16 m x 16 m'],
        'rental_prices' => ['Pirmdiena–ceturtdiena' => '70€'],
        'included_items' => ['enkurstieņi.'],
        'rental_terms' => 'Viena nomas diena.',
    ]);

    Livewire::test('pages::admin.product-form', ['product' => $product])
        ->assertSet('name', 'Krāsaina zvaigzne')
        ->assertSet('size', 'large')
        ->assertSet('isNew', true)
        ->assertSet('isForSale', true)
        ->assertSet('salePrice', '450.00')
        ->assertSet('description', '<p>Pirmā rindkopa.</p><p>Otrā rindkopa.</p>')
        ->assertSet('rentalTerms', '<p>Viena nomas diena.</p>')
        ->assertSet('specs', [['label' => 'Izmēri', 'value' => '16 m x 16 m']])
        ->assertSet('rentalPrices', [['label' => 'Pirmdiena–ceturtdiena', 'value' => '70€']])
        ->assertSet('includedItems', ['enkurstieņi.']);
});

test('a product can be updated without creating a duplicate', function () {
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create();

    Livewire::test('pages::admin.product-form', ['product' => $product])
        ->set('name', 'Atjaunināts produkts')
        ->set('price', '99.50')
        ->set('isNew', true)
        ->call('save')
        ->assertHasNoErrors();

    expect($product->refresh())
        ->name->toBe('Atjaunināts produkts')
        ->price->toBe('99.50')
        ->is_new->toBeTrue();

    expect(Product::query()->count())->toBe(1);
});

test('category, name and price are required', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.product-form')
        ->set('categoryId', '')
        ->set('name', '')
        ->set('price', '')
        ->call('save')
        ->assertHasErrors(['categoryId', 'name', 'price']);
});

test('the discount price must be lower than the standard price', function (string $discountPrice) {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();

    Livewire::test('pages::admin.product-form')
        ->set('categoryId', (string) $category->id)
        ->set('name', 'Produkts')
        ->set('price', '100')
        ->set('discountPrice', $discountPrice)
        ->call('save')
        ->assertHasErrors(['discountPrice']);
})->with(['equal' => '100', 'higher' => '110']);

test('a product can be marked for sale with a sale price', function () {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();

    Livewire::test('pages::admin.product-form')
        ->set('categoryId', (string) $category->id)
        ->set('name', 'Pārdodamais produkts')
        ->set('price', '130')
        ->set('isForSale', true)
        ->set('salePrice', '450')
        ->call('save')
        ->assertHasNoErrors();

    expect(Product::query()->sole())
        ->is_for_sale->toBeTrue()
        ->sale_price->toBe('450.00');
});

test('a sale price is required and must be valid when the product is for sale', function (?string $salePrice) {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();

    Livewire::test('pages::admin.product-form')
        ->set('categoryId', (string) $category->id)
        ->set('name', 'Produkts')
        ->set('price', '100')
        ->set('isForSale', true)
        ->set('salePrice', $salePrice)
        ->call('save')
        ->assertHasErrors(['salePrice']);
})->with(['missing' => '', 'not a number' => 'abc', 'too many decimals' => '12.345']);

test('unchecking available for sale clears the stored sale price', function () {
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->forSale()->create();

    Livewire::test('pages::admin.product-form', ['product' => $product])
        ->assertSet('isForSale', true)
        ->set('isForSale', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($product->refresh())
        ->is_for_sale->toBeFalse()
        ->sale_price->toBeNull();
});

test('the size is optional and an invalid size is rejected', function () {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();

    Livewire::test('pages::admin.product-form')
        ->set('categoryId', (string) $category->id)
        ->set('name', 'Bez izmēra')
        ->set('price', '50')
        ->set('size', '')
        ->call('save')
        ->assertHasNoErrors();

    expect(Product::query()->sole()->size)->toBeNull();

    Livewire::test('pages::admin.product-form')
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

    Livewire::test('pages::admin.product-form')
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

    Livewire::test('pages::admin.product-form', ['product' => $product])
        ->set('image', UploadedFile::fake()->image('jaunais.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($product->refresh()->path);
});

test('the main image can be removed', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $path = UploadedFile::fake()->image('foto.jpg')->store('products', 'public');
    $product = Product::factory()->create(['path' => $path]);

    Livewire::test('pages::admin.product-form', ['product' => $product])
        ->call('removeImage');

    expect($product->refresh()->path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

test('gallery uploads are optimized and appended in order', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create();
    ProductImage::factory()->for($product)->create(['position' => 0]);

    Livewire::test('pages::admin.product-form', ['product' => $product])
        ->set('galleryUploads', [
            UploadedFile::fake()->image('pirmais.jpg', 1600, 1200),
            UploadedFile::fake()->image('otrais.jpg', 1600, 1200),
        ])
        ->call('saveGalleryUploads')
        ->assertHasNoErrors();

    $images = $product->images()->get();

    expect($images)->toHaveCount(3)
        ->and($images->pluck('position')->all())->toBe([0, 1, 2])
        ->and($images->last()->path)->toStartWith('products/gallery/')
        ->and($images->last()->path)->toEndWith('.webp');

    Storage::disk('public')->assertExists($images->last()->path);
});

test('gallery images can be reordered', function () {
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create();

    $first = ProductImage::factory()->for($product)->create(['position' => 0]);
    $second = ProductImage::factory()->for($product)->create(['position' => 1]);
    $third = ProductImage::factory()->for($product)->create(['position' => 2]);

    Livewire::test('pages::admin.product-form', ['product' => $product])
        ->call('sortImages', $third->id, 0);

    expect($third->refresh()->position)->toBe(0)
        ->and($first->refresh()->position)->toBe(1)
        ->and($second->refresh()->position)->toBe(2);
});

test('a gallery image can be deleted and its file is removed', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create();
    $path = UploadedFile::fake()->image('galerija.jpg')->store('products/gallery', 'public');
    $image = ProductImage::factory()->for($product)->create(['path' => $path]);

    Livewire::test('pages::admin.product-form', ['product' => $product])
        ->call('deleteImage', $image->id);

    expect(ProductImage::query()->count())->toBe(0);
    Storage::disk('public')->assertMissing($path);
});

test('another product\'s gallery image cannot be deleted', function () {
    $this->actingAs(User::factory()->create());

    $product = Product::factory()->create();
    $foreign = ProductImage::factory()->create();

    Livewire::test('pages::admin.product-form', ['product' => $product])
        ->call('deleteImage', $foreign->id)
        ->assertStatus(404);

    expect(ProductImage::query()->count())->toBe(1);
});
