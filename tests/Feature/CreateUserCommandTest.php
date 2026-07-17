<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('a user can be created from the CLI', function () {
    $this->artisan('user:create', ['name' => 'John Doe', 'email' => 'john@example.com'])
        ->expectsQuestion('Password', 'secret-password')
        ->expectsQuestion('Confirm password', 'secret-password')
        ->assertSuccessful();

    $user = User::where('email', 'john@example.com')->first();

    expect($user)->not->toBeNull()
        ->and($user->name)->toBe('John Doe')
        ->and(Hash::check('secret-password', $user->password))->toBeTrue();
});

test('name and email are prompted when not passed as arguments', function () {
    $this->artisan('user:create')
        ->expectsQuestion('Name', 'Jane Doe')
        ->expectsQuestion('Email address', 'jane@example.com')
        ->expectsQuestion('Password', 'secret-password')
        ->expectsQuestion('Confirm password', 'secret-password')
        ->assertSuccessful();

    expect(User::where('email', 'jane@example.com')->exists())->toBeTrue();
});

test('the command fails when the email is already taken', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->artisan('user:create', ['name' => 'John Doe', 'email' => 'taken@example.com'])
        ->expectsQuestion('Password', 'secret-password')
        ->expectsQuestion('Confirm password', 'secret-password')
        ->assertFailed();

    expect(User::count())->toBe(1);
});

test('the command fails when the password confirmation does not match', function () {
    $this->artisan('user:create', ['name' => 'John Doe', 'email' => 'john@example.com'])
        ->expectsQuestion('Password', 'secret-password')
        ->expectsQuestion('Confirm password', 'different-password')
        ->assertFailed();

    expect(User::count())->toBe(0);
});

test('registration routes are disabled', function () {
    $this->get('/register')->assertNotFound();

    // POST matches the root-level category slug catch-all (GET only), hence 405 instead of 404.
    $this->post('/register', [
        'name' => 'John Doe',
        'email' => 'john@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertMethodNotAllowed();

    expect(User::count())->toBe(0);
});
