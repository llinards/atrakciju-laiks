<?php

use App\Models\Category;
use App\Models\Product;

test('product page renders name, price and static sections', function () {
    $category = Category::factory()->create();

    $product = Product::factory()->for($category)->create([
        'name' => 'JAUNUMS! Minecraft',
        'price' => 180,
    ]);

    $response = $this->get(route('product.show', [$category, $product]));

    $response->assertSuccessful();
    $response->assertSeeInOrder(['Atpakaļ', 'JAUNUMS! Minecraft'], escape: false);
    $response->assertSee('Nomas cena no 180€');
    $response->assertSee('Tehniskā informācija:');
    $response->assertSee('Garums');
    $response->assertSee('Nomas komplektā iekļauts:');
    $response->assertSee('Rezervēt');
    $response->assertSee('Par atrakciju');
    $response->assertSee('Noma un uzstādīšana');
    $response->assertSee('Košs dizains');
    $response->assertSee('Cena par vienu nomas dienu:');
});

test('product page title uses the product name', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create(['name' => 'Fortnite']);

    $this->get(route('product.show', [$category, $product]))
        ->assertSee('Fortnite - '.config('app.name'), escape: false);
});

test('discounted product shows struck original price', function () {
    $category = Category::factory()->create();

    $product = Product::factory()->for($category)->create([
        'price' => 160,
        'discount_price' => 130,
    ]);

    $response = $this->get(route('product.show', [$category, $product]));

    $response->assertSuccessful();
    $response->assertSee('Nomas cena no 130€');
    $response->assertSee('160€');
});

test('new product shows the JAUNUMS! badge on the detail page', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->isNew()->create();

    $this->get(route('product.show', [$category, $product]))->assertSee('JAUNUMS!');
});

test('regular product shows no JAUNUMS! badge on the detail page', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create();

    $this->get(route('product.show', [$category, $product]))->assertDontSee('JAUNUMS!');
});

test('back link points to the product category', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create();

    $this->get(route('product.show', [$category, $product]))
        ->assertSee(route('category.show', $category->slug), escape: false);
});

test('hidden product returns 404', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->hidden()->create();

    $this->get(route('product.show', [$category, $product]))->assertNotFound();
});

test('product under a hidden category returns 404', function () {
    $category = Category::factory()->hidden()->create();
    $product = Product::factory()->for($category)->create();

    $this->get(route('product.show', [$category, $product]))->assertNotFound();
});

test('product under the wrong category slug returns 404', function () {
    $product = Product::factory()->create();
    $otherCategory = Category::factory()->create();

    $this->get(route('product.show', [$otherCategory, $product]))->assertNotFound();
});

test('unknown product id returns 404', function () {
    $category = Category::factory()->create();

    $this->get(route('product.show', [$category, 999999]))->assertNotFound();
});

test('related products show visible siblings only, excluding the current product', function () {
    $category = Category::factory()->create();

    $product = Product::factory()->for($category)->create(['name' => 'Minecraft']);
    Product::factory()->for($category)->create(['name' => 'Fortnite']);
    Product::factory()->for($category)->hidden()->create(['name' => 'Slēptais produkts']);
    Product::factory()->create(['name' => 'Citas kategorijas produkts']);

    $response = $this->get(route('product.show', [$category, $product]));

    $response->assertSuccessful();
    $response->assertSee('Citas piepūšamās atrakcijas');
    $response->assertSee('Fortnite');
    $response->assertDontSee('Slēptais produkts');
    $response->assertDontSee('Citas kategorijas produkts');
    $response->assertDontSee(route('product.show', [$category, $product]).'"', escape: false);
});

test('related products section is hidden without siblings', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create();

    $this->get(route('product.show', [$category, $product]))
        ->assertDontSee('Citas piepūšamās atrakcijas');
});

test('related products are capped at 8 in display order', function () {
    $category = Category::factory()->create();

    $product = Product::factory()->for($category)->create(['position' => 0]);

    Product::factory()->for($category)->count(9)->sequence(
        fn ($sequence) => ['name' => 'Māsas produkts '.($sequence->index + 1), 'position' => $sequence->index + 1],
    )->create();

    $response = $this->get(route('product.show', [$category, $product]));

    $response->assertSuccessful();
    $response->assertSee('Māsas produkts 8');
    $response->assertDontSee('Māsas produkts 9');
});

test('category page cards link to the product page with wire:navigate', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create();

    $this->get(route('category.show', $category->slug))
        ->assertSee(route('product.show', [$category, $product]), escape: false)
        ->assertSee('wire:navigate', escape: false)
        ->assertDontSee('@if', escape: false);
});
