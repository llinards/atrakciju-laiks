<?php

test('login page renders in latvian', function () {
    $response = $this->get(route('login'));

    $response->assertOk()
        ->assertSee('Piesakieties savā kontā')
        ->assertSee('E-pasta adrese')
        ->assertSee('Atcerēties mani');
});

test('validation errors are returned in latvian', function () {
    $response = $this->from(route('login'))->post(route('login.store'), [
        'email' => 'linards@slmedia.lv',
        'password' => '',
    ]);

    $response->assertSessionHasErrors([
        'password' => 'Parole lauks ir obligāts.',
    ]);
});

test('home page renders in latvian', function () {
    $response = $this->get(route('home'));

    $response->assertOk()
        ->assertSee('Atrakciju noma');
});
