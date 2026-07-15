<?php

use App\Models\Setting;
use App\Models\User;
use Livewire\Livewire;

test('guests cannot view the site settings page', function () {
    $this->get(route('site-settings.edit'))->assertRedirect(route('login'));
});

test('site settings page is displayed with current values', function () {
    $this->actingAs(User::factory()->create());

    $response = $this->get(route('site-settings.edit'));

    $response->assertOk();
    $response->assertSee(config('site.phone'));
});

test('site settings can be updated and are reused on the public site', function () {
    $defaultPhone = config('site.phone');

    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.site-settings')
        ->set('phone', '+371 20000000')
        ->set('email', 'info@atrakcijulaiks.lv')
        ->set('address', 'Jaunā iela 1, Rīga, LV-1001')
        ->set('facebook', 'https://www.facebook.com/jauns')
        ->set('youtube', 'https://www.youtube.com/@jauns')
        ->call('updateSiteSettings')
        ->assertHasNoErrors();

    expect(Setting::query()->pluck('value', 'key')->all())->toBe([
        'phone' => '+371 20000000',
        'email' => 'info@atrakcijulaiks.lv',
        'address' => 'Jaunā iela 1, Rīga, LV-1001',
        'facebook' => 'https://www.facebook.com/jauns',
        'youtube' => 'https://www.youtube.com/@jauns',
    ]);

    $home = $this->get(route('home'));

    $home->assertSee('+371 20000000');
    $home->assertSee('info@atrakcijulaiks.lv');
    $home->assertDontSee($defaultPhone);
});

test('site settings are validated', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.site-settings')
        ->set('phone', '')
        ->set('email', 'not-an-email')
        ->set('facebook', 'not-a-url')
        ->call('updateSiteSettings')
        ->assertHasErrors(['phone', 'email', 'facebook']);

    expect(Setting::query()->count())->toBe(0);
});
