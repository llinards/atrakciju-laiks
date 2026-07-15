<?php

test('home page renders all sections', function () {
    $response = $this->get(route('home'));

    $response->assertSuccessful();

    $response->assertSeeInOrder([
        'Ko vēlies nomāt šodien?',
        'Prieks bez raizēm',
        'Biežāk uzdotie jautājumi',
    ]);

    $response->assertSee('Piepūšamās atrakcijas');
    $response->assertSee(config('site.phone'));
    $response->assertSee(config('site.email'));
});

test('home page does not load Flux assets', function () {
    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertDontSee('resources/css/app.css');
    $response->assertDontSee('data-flux-');
});
