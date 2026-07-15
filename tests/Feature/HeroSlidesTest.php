<?php

use App\Models\HeroSlide;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('guests cannot view the hero slides page', function () {
    $this->get(route('hero-slides.edit'))->assertRedirect(route('login'));
});

test('hero slides page is displayed', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('hero-slides.edit'))->assertOk();
});

test('hero images can be uploaded', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.hero-slides')
        ->set('images', [
            UploadedFile::fake()->image('slide-1.jpg', 1400, 600),
            UploadedFile::fake()->image('slide-2.png', 1400, 600),
        ])
        ->call('save')
        ->assertHasNoErrors();

    expect(HeroSlide::query()->count())->toBe(2);

    HeroSlide::query()->ordered()->get()->each(function (HeroSlide $slide) {
        Storage::disk('public')->assertExists($slide->path);
    });
});

test('no more than five hero images can be stored in total', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    HeroSlide::factory()->count(4)->create();

    Livewire::test('pages::admin.hero-slides')
        ->set('images', [
            UploadedFile::fake()->image('slide-5.jpg'),
            UploadedFile::fake()->image('slide-6.jpg'),
        ])
        ->call('save')
        ->assertHasErrors(['images']);

    expect(HeroSlide::query()->count())->toBe(4);
});

test('a hero image can be deleted', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $slides = HeroSlide::factory()->count(2)->create();

    Livewire::test('pages::admin.hero-slides')
        ->call('deleteSlide', $slides->first()->id);

    expect(HeroSlide::query()->count())->toBe(1);
});

test('the last hero image cannot be deleted', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $slide = HeroSlide::factory()->create();

    Livewire::test('pages::admin.hero-slides')
        ->call('deleteSlide', $slide->id);

    expect(HeroSlide::query()->count())->toBe(1);
});

test('uploaded hero images are shown on the home page', function () {
    HeroSlide::factory()->create(['path' => 'hero-slides/custom-slide.png']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('hero-slides/custom-slide.png')
        ->assertDontSee('images/hero-1.png');
});

test('home page falls back to the bundled hero image when none are uploaded', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('images/hero-1.png');
});
