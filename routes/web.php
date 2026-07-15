<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::public.home')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');

    Route::livewire('site-settings', 'pages::admin.site-settings')->name('site-settings.edit');
});

require __DIR__.'/settings.php';
