<?php

test('unknown URLs render the branded 404 page', function () {
    $response = $this->get('/sada-lapa-neeksiste');

    $response->assertNotFound();
    $response->assertSee('Lapa nav atrasta');
    $response->assertSee('images/logo.png');
    $response->assertSee('Uz sākumlapu');
});

test('session expired page offers a refresh', function () {
    $this->view('errors.419')
        ->assertSee('Sesija ir beigusies')
        ->assertSee('Atsvaidzināt lapu');
});

test('rate limit page offers to go back', function () {
    $this->view('errors.429')
        ->assertSee('Pārāk daudz pieprasījumu')
        ->assertSee('Atpakaļ');
});

test('server error page shows contact details', function () {
    $this->view('errors.500')
        ->assertSee('Kaut kas nogāja greizi')
        ->assertSee(config('site.phone'))
        ->assertSee(config('site.email'));
});

test('maintenance page offers a refresh', function () {
    $this->view('errors.503')
        ->assertSee('Notiek plānota apkope')
        ->assertSee('Atsvaidzināt lapu');
});

test('error pages are excluded from search indexing', function () {
    $this->get('/sada-lapa-neeksiste')
        ->assertSee('<meta name="robots" content="noindex"', escape: false);
});
