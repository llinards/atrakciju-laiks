<?php

use App\Models\Category;
use App\Models\Faq;

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

test('header menu links to attraction size filters', function () {
    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertDontSee('Visas piepūšamās atrakcijas');
    $response->assertSee('Lielās atrakcijas');
    $response->assertSee('Vidējās atrakcijas');
    $response->assertSee('Mazās atrakcijas');
    $response->assertSee(route('category.show', ['category' => 'piepusamas-atrakcijas', 'size' => 'large']), escape: false);
});

test('home page does not load Flux assets', function () {
    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertDontSee('resources/css/app.css');
    $response->assertDontSee('data-flux-');
});
