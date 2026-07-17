<?php

use Illuminate\Support\Facades\Facade;
use Whitecube\LaravelCookieConsent\CookiesManager;

test('the cookie banner is shown to new visitors', function () {
    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertSee('Mēs izmantojam sīkdatnes');
    $response->assertSee(route('privacy'), escape: false);
    $response->assertSee(route('cookieconsent.script'), escape: false);
});

test('accepting cookies sets the consent cookie', function () {
    $response = $this->post(route('cookieconsent.accept.all'));

    $response->assertRedirect();
    $response->assertCookieNotExpired(config('cookieconsent.cookie.name'));
});

test('the banner is not shown once consent has been given', function () {
    // The same shape the package's accept endpoints store — the cookie is
    // written and read unencrypted (see the EncryptCookies exception).
    $consent = json_encode([
        config('cookieconsent.cookie.name') => true,
        config('session.cookie') => true,
        'XSRF-TOKEN' => true,
        'consent_at' => now()->unix(),
    ]);

    // The Cookies facade caches a manager built during app boot (with an
    // empty request); in real HTTP the app boots per request so the manager
    // always sees the actual cookies. Reset it to mirror that.
    Facade::clearResolvedInstance(CookiesManager::class);

    $response = $this->withUnencryptedCookie(config('cookieconsent.cookie.name'), $consent)
        ->get(route('home'));

    $response->assertSuccessful();
    $response->assertDontSee('Mēs izmantojam sīkdatnes');
});
