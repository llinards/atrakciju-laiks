<?php

test('privacy policy page renders with the cookies table', function () {
    $response = $this->get(route('privacy'));

    $response->assertSuccessful();
    $response->assertSee('Privātuma politika');
    $response->assertSee('Nepieciešamās sīkdatnes');

    // Every registered cookie appears in the table.
    $response->assertSee(config('cookieconsent.cookie.name'));
    $response->assertSee(config('session.cookie'));
    $response->assertSee('XSRF-TOKEN');

    $response->assertSee(config('site.email'));
});

test('privacy policy page has SEO meta and canonical', function () {
    $response = $this->get(route('privacy'));

    $response->assertSee('<link rel="canonical" href="'.route('privacy').'"', escape: false);
    $response->assertSee('<meta name="description"', escape: false);
});

test('privacy policy is listed in the sitemap', function () {
    $this->get(route('sitemap'))
        ->assertSee('<loc>'.route('privacy').'</loc>', escape: false);
});

test('footer links to the privacy policy', function () {
    $this->get(route('home'))
        ->assertSee('Privātuma politika');
});
