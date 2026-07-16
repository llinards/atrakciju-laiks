<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Setting;

test('home page includes the reserve modal with contact links', function () {
    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertSee('Sazinies un rezervē!');
    $response->assertSee('zvani vai raksti e-pastā');
    $response->assertSee('tel:'.str_replace(' ', '', config('site.phone')), escape: false);
    $response->assertSee('mailto:'.config('site.email'), escape: false);
    $response->assertSee('open-reserve-modal');
});

test('home CTA opens the modal instead of linking to email', function () {
    $response = $this->get(route('home'));

    $response->assertSeeInOrder(['@click="$dispatch(\'open-reserve-modal\')"', 'Sazinies ar mums!'], escape: false);
});

test('product page reserve button opens the modal', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create();

    $response = $this->get(route('product.show', [$category, $product]));

    $response->assertSuccessful();
    $response->assertSee('Rezervēt');
    $response->assertSee('Sazinies un rezervē!');
    $response->assertSee('open-reserve-modal');
    $response->assertSee('tel:'.str_replace(' ', '', config('site.phone')), escape: false);
    $response->assertSee('mailto:'.config('site.email'), escape: false);
});

test('reserve modal uses contact details from stored settings', function () {
    Setting::set('phone', '+371 20000000');
    Setting::set('email', 'info@atrakcijulaiks.lv');

    $response = $this->get(route('home'));

    $response->assertSee('tel:+37120000000', escape: false);
    $response->assertSee('mailto:info@atrakcijulaiks.lv', escape: false);
});
