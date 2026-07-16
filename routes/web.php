<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::public.home')->name('home');

Route::livewire('/kontakti', 'pages::public.contact')->name('contact');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::livewire('dashboard', 'pages::admin.dashboard')->name('dashboard');

    Route::livewire('site-settings', 'pages::admin.site-settings')->name('site-settings.edit');

    Route::livewire('hero-slides', 'pages::admin.hero-slides')->name('hero-slides.edit');

    Route::livewire('faqs', 'pages::admin.faqs')->name('faqs.edit');

    Route::livewire('categories', 'pages::admin.categories')->name('categories.edit');

    Route::livewire('products', 'pages::admin.products')->name('products.index');

    Route::livewire('products/create', 'pages::admin.product-form')->name('products.create');

    Route::livewire('products/{product}/edit', 'pages::admin.product-form')->name('products.edit');
});

require __DIR__.'/settings.php';

// Products flagged for sale, across all categories. Registered before the
// wildcards so "pardosana" wins over category slugs (and becomes a reserved
// path via NotReservedPath automatically).
Route::livewire('/pardosana', 'pages::public.sale')->name('sale.index');

// Product slugs are only unique per category, so this root-level binding
// resolves the first global slug match; the page 404s unless that product
// is actually for sale.
Route::livewire('/pardosana/{product:slug}', 'pages::public.product')->name('sale.show');

// Products are scoped to their category, so a product slug under the wrong
// category slug returns a 404.
Route::livewire('/{category:slug}/{product:slug}', 'pages::public.product')
    ->scopeBindings()
    ->name('product.show');

// Root-level category slugs — must stay the LAST registered route so
// every explicitly defined path above wins over the wildcard.
Route::livewire('/{category:slug}', 'pages::public.category')->name('category.show');
