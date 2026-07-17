<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;

test('product page renders name and price without detail sections for a bare product', function () {
    $category = Category::factory()->create();

    $product = Product::factory()->for($category)->create([
        'name' => 'JAUNUMS! Minecraft',
        'price' => 180,
    ]);

    $response = $this->get(route('product.show', [$category, $product]));

    $response->assertSuccessful();
    $response->assertSeeInOrder(['Atpakaļ', 'JAUNUMS! Minecraft'], escape: false);
    $response->assertSee('Nomas cena no 180€');
    $response->assertSee('Rezervēt');

    // Detail sections only render once their data exists.
    $response->assertDontSee('Tehniskā informācija:');
    $response->assertDontSee('Nomas komplektā iekļauts:');
    $response->assertDontSee('Par atrakciju');
    $response->assertDontSee('Noma un uzstādīšana');
    $response->assertDontSee('Cena par vienu nomas dienu:');
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

test('stored gallery images replace the placeholder gallery', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create();

    ProductImage::factory()->for($product)->create(['path' => 'products/gallery/pirmais.webp', 'position' => 0]);
    ProductImage::factory()->for($product)->create(['path' => 'products/gallery/otrais.webp', 'position' => 1]);

    $response = $this->get(route('product.show', [$category, $product]));

    $response->assertSuccessful();
    $response->assertSee('products/gallery/pirmais.webp');
    $response->assertSee('products/gallery/otrais.webp');
    $response->assertDontSee('images/about-1.png');
});

test('products without a gallery show only their main image', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create();

    $this->get(route('product.show', [$category, $product]))
        ->assertDontSee('images/about-1.png');
});

test('stored detail content replaces the static placeholders', function () {
    $category = Category::factory()->create();

    $product = Product::factory()->for($category)->create([
        'name' => 'Krāsaina zvaigzne',
        'price' => 70,
        'description' => '<p>Rekomendējama dažāda veida pasākumiem.</p>'
            .'<p>Telts izgatavota no izturīga PVC materiāla.</p>'
            .'<ul><li>Bērniem no 3 gadu vecuma</li><li>Līdz 6 bērniem vienlaicīgi</li></ul>',
        'specs' => ['Izmēri' => '16 m x 16 m', 'Cilvēku skaits' => 'Līdz 40'],
        'rental_prices' => ['Pirmdiena–ceturtdiena' => '70€', 'Piektdiena–svētdiena' => '70€'],
        'included_items' => ['telts soma ar 3-daļīgu mastu;', 'enkurstieņi.'],
        'rental_terms' => "Viena nomas diena ir no plkst. 9:00 līdz plkst. 20:00.\n\nPiegāde: 0,40 €/km.",
    ]);

    $response = $this->get(route('product.show', [$category, $product]));

    $response->assertSuccessful();
    $response->assertSee('Rekomendējama dažāda veida pasākumiem.');
    $response->assertSee('Telts izgatavota no izturīga PVC materiāla.');
    $response->assertSee('Izmēri');
    $response->assertSee('16 m x 16 m');
    $response->assertSee('telts soma ar 3-daļīgu mastu;');
    $response->assertSee('Pirmdiena–ceturtdiena');
    $response->assertSee('Bērniem no 3 gadu vecuma');
    $response->assertSee('Līdz 6 bērniem vienlaicīgi');
    $response->assertSee('Viena nomas diena ir no plkst. 9:00 līdz plkst. 20:00.');
    $response->assertSee('Piegāde: 0,40 €/km.');

    // The demo placeholders must be gone once real content exists.
    $response->assertDontSee('Košs dizains');
    $response->assertDontSee('Garums');
    $response->assertDontSee('Klientam jānodrošina');
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

test('unknown product slug returns 404', function () {
    $category = Category::factory()->create();

    $this->get(route('product.show', [$category, 'neeksistejošs-produkts']))->assertNotFound();
});

test('product URLs use the slug derived from the name', function () {
    $category = Category::factory()->create(['slug' => 'teltis']);
    $product = Product::factory()->for($category)->create(['name' => 'Krāsaina zvaigzne']);

    expect($product->slug)->toBe('krasaina-zvaigzne')
        ->and(route('product.show', [$category, $product]))->toContain('/teltis/krasaina-zvaigzne');

    $this->get(route('product.show', [$category, $product]))->assertSuccessful();
});

test('duplicate product names in a category get suffixed slugs', function () {
    $category = Category::factory()->create();

    Product::factory()->for($category)->create(['name' => 'Zvaigzne']);
    $second = Product::factory()->for($category)->create(['name' => 'Zvaigzne']);

    expect($second->slug)->toBe('zvaigzne-2');
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
    // The page's own URL now appears in the canonical link, so the related
    // list is checked via the cards' wire:key instead.
    $response->assertDontSee('wire:key="related-'.$product->id.'"', escape: false);
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
