<?php

use App\Enums\ProductSize;
use App\Models\Category;
use App\Models\Product;
use Livewire\Livewire;

test('category page renders title, description and visible products', function () {
    $category = Category::factory()->create([
        'title' => 'Piepūšamās atrakcijas',
        'slug' => 'piepusamas-atrakcijas',
        'description' => 'Atrakcijas bērnu ballītēm, pasākumiem un aktīvai atpūtai visā Latvijā.',
    ]);

    Product::factory()->for($category)->create(['name' => 'Piepūšamā pils "Džungļi"', 'price' => 130]);

    $response = $this->get(route('category.show', $category->slug));

    $response->assertSuccessful();
    $response->assertSeeInOrder([
        'Atpakaļ',
        'Piepūšamās atrakcijas',
        'Atrakcijas bērnu ballītēm, pasākumiem un aktīvai atpūtai visā Latvijā.',
        'Piepūšamā pils &quot;Džungļi&quot;',
    ], escape: false);
    $response->assertSee('Cena nomai 130€');
});

test('discounted product shows struck original price and computed percent badge', function () {
    $category = Category::factory()->create();

    Product::factory()->for($category)->create([
        'name' => 'Smurfi',
        'price' => 160,
        'discount_price' => 130,
    ]);

    $response = $this->get(route('category.show', $category->slug));

    $response->assertSuccessful();
    $response->assertSee('Cena nomai 130€');
    $response->assertSee('160€');
    $response->assertSee('19% atlaide');
});

test('new product shows the JAUNUMS! badge on its card', function () {
    $category = Category::factory()->create();

    Product::factory()->for($category)->isNew()->create(['name' => 'Minecraft']);
    Product::factory()->for($category)->create(['name' => 'Fortnite']);

    $response = $this->get(route('category.show', $category->slug));

    $response->assertSuccessful();
    expect(substr_count($response->getContent(), 'JAUNUMS!'))->toBe(1);
});

test('product without discount shows no atlaide badge', function () {
    $category = Category::factory()->create();

    Product::factory()->for($category)->create(['price' => 130]);

    $this->get(route('category.show', $category->slug))->assertDontSee('atlaide');
});

test('a discount price equal to or above the price is not shown as a discount', function () {
    $category = Category::factory()->create();

    $product = Product::factory()->for($category)->create([
        'price' => 130,
        'discount_price' => 130,
    ]);

    expect($product->formattedOriginalPrice())->toBeNull()
        ->and($product->discountPercent())->toBeNull();

    $this->get(route('category.show', $category->slug))->assertDontSee('atlaide');
});

test('hidden category returns 404', function () {
    $category = Category::factory()->hidden()->create();

    $this->get(route('category.show', $category->slug))->assertNotFound();
});

test('unknown category slug returns 404', function () {
    $this->get('/neeksiste-tada-kategorija')->assertNotFound();
});

test('hidden products and products from other categories are not shown', function () {
    $category = Category::factory()->create();

    Product::factory()->for($category)->hidden()->create(['name' => 'Slēptais produkts']);
    Product::factory()->create(['name' => 'Citas kategorijas produkts']);

    $response = $this->get(route('category.show', $category->slug));

    $response->assertSuccessful();
    $response->assertDontSee('Slēptais produkts');
    $response->assertDontSee('Citas kategorijas produkts');
});

test('products are paginated by 12 with a page counter', function () {
    $category = Category::factory()->create();

    Product::factory()->for($category)->count(13)->sequence(
        fn ($sequence) => ['name' => 'Produkts numur '.($sequence->index + 1), 'position' => $sequence->index],
    )->create();

    $response = $this->get(route('category.show', $category->slug));

    $response->assertSuccessful();
    $response->assertSee('Produkts numur 12');
    $response->assertDontSee('Produkts numur 13');
    $response->assertSee('1/2');

    Livewire::test('pages::public.category', ['category' => $category])
        ->call('nextPage')
        ->assertSee('Produkts numur 13')
        ->assertDontSee('Produkts numur 12');
});

test('size filter buttons render only when the category has sized products', function () {
    $withSizes = Category::factory()->create();
    Product::factory()->for($withSizes)->sized(ProductSize::Large)->create();

    $withoutSizes = Category::factory()->create();
    Product::factory()->for($withoutSizes)->create();

    $this->get(route('category.show', $withSizes->slug))
        ->assertSee('Filtrēt')
        ->assertSee('Lielās');

    $this->get(route('category.show', $withoutSizes->slug))
        ->assertDontSee('Filtrēt');
});

test('size filter shows only matching products and toggles off', function () {
    $category = Category::factory()->create();

    Product::factory()->for($category)->sized(ProductSize::Large)->create(['name' => 'Lielā pils']);
    Product::factory()->for($category)->sized(ProductSize::Small)->create(['name' => 'Mazā pils']);

    Livewire::test('pages::public.category', ['category' => $category])
        ->call('filterSize', 'large')
        ->assertSee('Lielā pils')
        ->assertDontSee('Mazā pils')
        ->call('filterSize', 'large')
        ->assertSee('Lielā pils')
        ->assertSee('Mazā pils');
});

test('invalid size filter value is ignored', function () {
    $category = Category::factory()->create();

    Product::factory()->for($category)->sized(ProductSize::Large)->create(['name' => 'Lielā pils']);

    Livewire::test('pages::public.category', ['category' => $category])
        ->call('filterSize', 'nepareizs')
        ->assertSet('size', null)
        ->assertSee('Lielā pils');
});

test('changing the size filter resets pagination to the first page', function () {
    $category = Category::factory()->create();

    Product::factory()->for($category)->count(13)->sized(ProductSize::Large)->create();

    Livewire::test('pages::public.category', ['category' => $category])
        ->call('nextPage')
        ->call('filterSize', 'large')
        ->assertSet('paginators.page', 1);
});

test('category slugs do not shadow explicitly defined routes', function () {
    Category::factory()->create(['slug' => 'faqs']);

    $this->get('/faqs')->assertRedirect(route('login'));
});
