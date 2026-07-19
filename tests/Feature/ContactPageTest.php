<?php

use App\Models\Category;
use App\Models\Setting;

test('contact page renders contact details and map', function () {
    $response = $this->get(route('contact'));

    $response->assertSuccessful();
    $response->assertSee('Kontakti');
    $response->assertSee(config('site.phone'));
    $response->assertSee(config('site.email'));
    $response->assertSee('"Dievausēni"');
    // The postal code is wrapped so it never line-breaks after the hyphen.
    $response->assertSee('Cēsu novads, <span class="whitespace-nowrap">LV-4113</span>', escape: false);
    $response->assertSee(config('site.facebook'), escape: false);
    $response->assertSee('https://www.google.com/maps?q=', escape: false);
});

test('contact page reflects admin-managed settings', function () {
    Setting::set('phone', '+371 20000000');
    Setting::set('address', 'Jaunā iela 1, Rīga, LV-1001');

    $response = $this->get(route('contact'));

    $response->assertSee('+371 20000000');
    $response->assertSee('Jaunā iela 1, Rīga, <span class="whitespace-nowrap">LV-1001</span>', escape: false);
    // The map embed URL keeps the raw address, without the nowrap markup.
    $response->assertSee(urlencode('Jaunā iela 1, Rīga, LV-1001'), escape: false);
});

test('header and footer link to the contact page', function () {
    $this->get(route('home'))->assertSee(route('contact'));
});

test('category slugs do not shadow the contact page', function () {
    Category::factory()->create(['slug' => 'kontakti']);

    $this->get('/kontakti')->assertSee('https://www.google.com/maps?q=', escape: false);
});
