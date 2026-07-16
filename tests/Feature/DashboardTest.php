<?php

use App\Models\Category;
use App\Models\Faq;
use App\Models\HeroSlide;
use App\Models\Product;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('dashboard shows content counts', function () {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();
    Product::factory()->for($category)->count(3)->create(['is_visible' => true]);
    Product::factory()->for($category)->create(['is_visible' => false]);
    Faq::factory()->create(['is_visible' => false]);
    HeroSlide::factory()->count(2)->create();

    Livewire::test('pages::admin.dashboard')
        ->assertSee(__(':count visible', ['count' => 3]))
        ->assertSee(__(':count visible', ['count' => 0]))
        ->assertSee('2 / 5')
        ->assertSee(config('site.phone'))
        ->assertSee(config('site.email'));
});

test('dashboard shows quick action links', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.dashboard')
        ->assertSeeHtml(route('products.create'))
        ->assertSeeHtml(route('products.index'))
        ->assertSeeHtml(route('categories.edit'))
        ->assertSeeHtml(route('faqs.edit'))
        ->assertSeeHtml(route('hero-slides.edit'))
        ->assertSeeHtml(route('site-settings.edit'))
        ->assertSee(__('View public site'));
});
