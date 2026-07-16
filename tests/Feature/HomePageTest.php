<?php

use App\Enums\ProductSize;
use App\Models\Category;
use App\Models\Faq;
use App\Models\Product;

test('home page renders all sections', function () {
    Category::factory()->create(['title' => 'Piepūšamās pilis', 'slug' => 'piepusamas-pilis']);
    Faq::factory()->create(['question' => 'Vai ir iespējama piegāde?']);

    $response = $this->get(route('home'));

    $response->assertSuccessful();

    $response->assertSeeInOrder([
        'Ko vēlies nomāt šodien?',
        'Prieks bez raizēm',
        'Biežāk uzdotie jautājumi',
    ]);

    $response->assertSee('Piepūšamās pilis');
    $response->assertSee(route('category.show', 'piepusamas-pilis'));
    $response->assertSee(config('site.phone'));
    $response->assertSee(config('site.email'));
});

test('category section is hidden when no visible categories exist', function () {
    Category::factory()->hidden()->create();

    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertDontSee('Ko vēlies nomāt šodien?');
});

test('header menu shows size filter links for categories with sized products', function () {
    $withSizes = Category::factory()->create(['title' => 'Piepūšamās atrakcijas', 'slug' => 'piepusamas-atrakcijas']);
    Product::factory()->for($withSizes)->sized(ProductSize::Large)->create();

    $plain = Category::factory()->create(['title' => 'Teltis', 'slug' => 'teltis']);
    Product::factory()->for($plain)->create();

    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertSee('Lielās piepūšamās atrakcijas');
    $response->assertSee('Vidējās piepūšamās atrakcijas');
    $response->assertSee('Mazās piepūšamās atrakcijas');
    $response->assertSee(route('category.show', ['category' => 'piepusamas-atrakcijas', 'size' => 'large']), escape: false);
    $response->assertSee(route('category.show', 'teltis'), escape: false);
});

test('hidden categories are not shown in the header or footer menu', function () {
    Category::factory()->create(['title' => 'Redzamā kategorija']);
    Category::factory()->hidden()->create(['title' => 'Paslēptā kategorija']);

    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertSee('Redzamā kategorija');
    $response->assertDontSee('Paslēptā kategorija');
});

test('home page does not load Flux assets', function () {
    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertDontSee('resources/css/app.css');
    $response->assertDontSee('data-flux-');
});
