<?php

use App\Models\Category;
use App\Models\Product;
use Livewire\Livewire;

test('sale listing renders for-sale products with the sale price', function () {
    $category = Category::factory()->create();

    Product::factory()->for($category)->forSale()->create([
        'name' => 'Piepūšamā pils "Džungļi"',
        'price' => 130,
        'sale_price' => 520,
    ]);

    $response = $this->get(route('sale.index'));

    $response->assertSuccessful();
    $response->assertSeeInOrder(['Atpakaļ', 'Piepūšamā pils &quot;Džungļi&quot;'], escape: false);
    $response->assertSee('Pārdošanas cena: 520€');
    $response->assertDontSee('Cena nomai');
    $response->assertSee('Sazināties');
});

test('sale listing page title uses the section name', function () {
    $this->get(route('sale.index'))
        ->assertSee('Pārdošanas sadaļa - '.config('app.name'), escape: false);
});

test('rental-only and hidden for-sale products are not listed', function () {
    Product::factory()->create(['name' => 'Tikai nomai']);
    Product::factory()->forSale()->hidden()->create(['name' => 'Slēptais produkts']);

    $response = $this->get(route('sale.index'));

    $response->assertSuccessful();
    $response->assertDontSee('Tikai nomai');
    $response->assertDontSee('Slēptais produkts');
    $response->assertSee('Pašlaik pārdošanā nav pieejamu produktu.');
});

test('for-sale products from all categories appear, even under a hidden category', function () {
    Product::factory()->forSale()->create(['name' => 'Pirmais produkts']);
    Product::factory()->forSale()->create(['name' => 'Otrais produkts']);
    Product::factory()->for(Category::factory()->hidden())->forSale()->create(['name' => 'Slēptās kategorijas produkts']);

    $response = $this->get(route('sale.index'));

    $response->assertSuccessful();
    $response->assertSee('Pirmais produkts');
    $response->assertSee('Otrais produkts');
    $response->assertSee('Slēptās kategorijas produkts');
});

test('sale cards show no JAUNUMS! or discount badge', function () {
    Product::factory()->forSale()->isNew()->discounted()->create(['price' => 160]);

    $response = $this->get(route('sale.index'));

    $response->assertSuccessful();
    $response->assertDontSee('JAUNUMS!');
    $response->assertDontSee('atlaide');
});

test('sale cards link to the sale detail page with wire:navigate', function () {
    $product = Product::factory()->forSale()->create();

    $this->get(route('sale.index'))
        ->assertSee(route('sale.show', $product), escape: false)
        ->assertSee('wire:navigate', escape: false);
});

test('sale listing is paginated by 12 with a page counter', function () {
    Product::factory()->count(13)->forSale()->sequence(
        fn ($sequence) => ['name' => 'Produkts numur '.($sequence->index + 1), 'position' => $sequence->index],
    )->create();

    $response = $this->get(route('sale.index'));

    $response->assertSuccessful();
    $response->assertSee('Produkts numur 12');
    $response->assertDontSee('Produkts numur 13');
    $response->assertSee('1/2');

    Livewire::test('pages::public.sale')
        ->call('nextPage')
        ->assertSee('Produkts numur 13')
        ->assertDontSee('Produkts numur 12');
});

test('sale detail page shows the sale price and contact CTA without rental content', function () {
    $product = Product::factory()->forSale()->isNew()->create([
        'name' => 'Krāsaina zvaigzne',
        'price' => 70,
        'sale_price' => 950,
        'description' => '<p>Rekomendējama dažāda veida pasākumiem.</p>',
        'included_items' => ['enkurstieņi.'],
        'rental_prices' => ['Pirmdiena–ceturtdiena' => '70€'],
        'rental_terms' => 'Viena nomas diena ir no plkst. 9:00 līdz plkst. 20:00.',
    ]);

    $response = $this->get(route('sale.show', $product));

    $response->assertSuccessful();
    $response->assertSee('Krāsaina zvaigzne');
    $response->assertSee('Pārdošanas cena: 950€');
    $response->assertSee('Sazināties');

    // The rental-only content and badges stay hidden in sale mode.
    $response->assertDontSee('Nomas cena');
    $response->assertDontSee('Rezervēt');
    $response->assertDontSee('Noma un uzstādīšana');
    $response->assertDontSee('Cena par vienu nomas dienu:');
    $response->assertDontSee('JAUNUMS!');

    // The description tab and included items keep rendering, without rental wording.
    $response->assertSee('Rekomendējama dažāda veida pasākumiem.');
    $response->assertSee('enkurstieņi.');
    $response->assertSee('Komplektā iekļauts:');
    $response->assertDontSee('Nomas komplektā iekļauts:');
});

test('sale detail back link points to the sale listing', function () {
    $product = Product::factory()->forSale()->create();

    $this->get(route('sale.show', $product))
        ->assertSee(route('sale.index'), escape: false);
});

test('sale detail works for a product under a hidden category', function () {
    $product = Product::factory()->for(Category::factory()->hidden())->forSale()->create();

    $this->get(route('sale.show', $product))->assertSuccessful();
});

test('sale detail returns 404 for rental-only and hidden products', function () {
    $rentalOnly = Product::factory()->create();
    $hidden = Product::factory()->forSale()->hidden()->create();

    $this->get(route('sale.show', $rentalOnly))->assertNotFound();
    $this->get(route('sale.show', $hidden))->assertNotFound();
});

test('a for-sale product keeps its rental listing and detail page', function () {
    $category = Category::factory()->create();

    $product = Product::factory()->for($category)->forSale()->create([
        'name' => 'Minecraft',
        'price' => 180,
        'sale_price' => 720,
    ]);

    $this->get(route('category.show', $category->slug))
        ->assertSee('Minecraft')
        ->assertSee('Cena nomai 180€');

    $this->get(route('product.show', [$category, $product]))
        ->assertSee('Nomas cena 180€')
        ->assertSee('Rezervēt')
        ->assertDontSee('Pārdošanas cena:');
});

test('sale detail related carousel shows other for-sale products only', function () {
    $product = Product::factory()->forSale()->create(['name' => 'Minecraft']);
    $sibling = Product::factory()->forSale()->create(['name' => 'Fortnite', 'sale_price' => 640]);
    Product::factory()->create(['name' => 'Tikai nomai']);
    Product::factory()->forSale()->hidden()->create(['name' => 'Slēptais produkts']);

    $response = $this->get(route('sale.show', $product));

    $response->assertSuccessful();
    $response->assertSee('Citi produkti pārdošanā');
    $response->assertDontSee('Citas piepūšamās atrakcijas');
    $response->assertSee('Fortnite');
    $response->assertSee(route('sale.show', $sibling), escape: false);
    $response->assertDontSee('Tikai nomai');
    $response->assertDontSee('Slēptais produkts');
});

test('sale detail related carousel is hidden without other for-sale products', function () {
    $product = Product::factory()->forSale()->create();
    Product::factory()->create();

    $this->get(route('sale.show', $product))
        ->assertDontSee('Citi produkti pārdošanā');
});
