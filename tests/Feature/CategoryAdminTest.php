<?php

use App\Enums\CategoryColor;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('guests cannot view the categories admin page', function () {
    $this->get(route('categories.edit'))->assertRedirect(route('login'));
});

test('categories admin page is displayed', function () {
    $this->actingAs(User::factory()->create());

    $this->get(route('categories.edit'))->assertOk();
});

test('a category can be created', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.categories')
        ->call('create')
        ->set('title', 'Batuti')
        ->call('save')
        ->assertHasNoErrors();

    expect(Category::query()->sole())
        ->title->toBe('Batuti')
        ->slug->toBe('batuti')
        ->is_visible->toBeTrue();
});

test('the slug is generated from the title when creating', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.categories')
        ->call('create')
        ->set('title', 'Piepūšamās pilis')
        ->call('save')
        ->assertHasNoErrors();

    expect(Category::query()->sole()->slug)->toBe('piepusamas-pilis');
});

test('the slug is not changed when editing the title', function () {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();
    $originalSlug = $category->slug;

    Livewire::test('pages::admin.categories')
        ->call('edit', $category->id)
        ->set('title', 'Jauns nosaukums')
        ->call('save')
        ->assertHasNoErrors();

    expect($category->refresh())
        ->title->toBe('Jauns nosaukums')
        ->slug->toBe($originalSlug);
});

test('a category can be updated', function () {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();
    $originalColor = $category->color;

    Livewire::test('pages::admin.categories')
        ->call('edit', $category->id)
        ->assertSet('title', $category->title)
        ->set('title', 'Atjaunināta kategorija')
        ->set('description', 'Jauns apraksts')
        ->call('save')
        ->assertHasNoErrors();

    expect($category->refresh())
        ->title->toBe('Atjaunināta kategorija')
        ->description->toBe('Jauns apraksts')
        ->color->toBe($originalColor);

    expect(Category::query()->count())->toBe(1);
});

test('a duplicate title gets a suffixed slug', function () {
    $this->actingAs(User::factory()->create());

    Category::factory()->create(['slug' => 'teltis']);

    Livewire::test('pages::admin.categories')
        ->call('create')
        ->set('title', 'Teltis')
        ->call('save')
        ->assertHasNoErrors();

    expect(Category::query()->where('slug', 'teltis-2')->exists())->toBeTrue();
});

test('the generated slug is url safe', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.categories')
        ->call('create')
        ->set('title', 'Lielā & Jautrā Zona!')
        ->call('save')
        ->assertHasNoErrors();

    expect(Category::query()->sole()->slug)->toBe('liela-jautra-zona');
});

test('titles colliding with reserved paths get a suffixed slug', function (string $title, string $expectedSlug) {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.categories')
        ->call('create')
        ->set('title', $title)
        ->call('save')
        ->assertHasNoErrors();

    expect(Category::query()->sole()->slug)->toBe($expectedSlug);
})->with([
    'route path' => ['Dashboard', 'dashboard-2'],
    'admin path' => ['Categories', 'categories-2'],
    'sale route path' => ['Pardosana', 'pardosana-2'],
    'static reserved path' => ['Livewire', 'livewire-2'],
]);

test('card colors rotate automatically for new categories', function () {
    $this->actingAs(User::factory()->create());

    foreach (['Pirmā', 'Otrā', 'Trešā'] as $title) {
        Livewire::test('pages::admin.categories')
            ->call('create')
            ->set('title', $title)
            ->call('save')
            ->assertHasNoErrors();
    }

    expect(Category::query()->where('title', 'Pirmā')->sole()->color)->toBe(CategoryColor::Splash)
        ->and(Category::query()->where('title', 'Otrā')->sole()->color)->toBe(CategoryColor::Brand)
        ->and(Category::query()->where('title', 'Trešā')->sole()->color)->toBe(CategoryColor::Sun);
});

test('a category image is optimized and stored as webp', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.categories')
        ->call('create')
        ->set('title', 'Batuti')
        ->set('image', UploadedFile::fake()->image('foto.jpg', 2000, 1500))
        ->call('save')
        ->assertHasNoErrors();

    $category = Category::query()->sole();

    expect($category->path)->toEndWith('.webp');
    Storage::disk('public')->assertExists($category->path);
});

test('replacing the image deletes the old file', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $oldPath = UploadedFile::fake()->image('vecais.jpg')->store('categories', 'public');
    $category = Category::factory()->create(['path' => $oldPath]);

    Livewire::test('pages::admin.categories')
        ->call('edit', $category->id)
        ->set('image', UploadedFile::fake()->image('jaunais.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($category->refresh()->path);
});

test('the category image can be removed', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $path = UploadedFile::fake()->image('foto.jpg')->store('categories', 'public');
    $category = Category::factory()->create(['path' => $path]);

    Livewire::test('pages::admin.categories')
        ->call('removeImage', $category->id);

    Storage::disk('public')->assertMissing($path);
    expect($category->refresh()->path)->toBeNull();
});

test('deleting a category deletes its products and all image files', function () {
    Storage::fake('public');
    $this->actingAs(User::factory()->create());

    $categoryPath = UploadedFile::fake()->image('kategorija.jpg')->store('categories', 'public');
    $productPath = UploadedFile::fake()->image('produkts.jpg')->store('products', 'public');

    $category = Category::factory()->create(['path' => $categoryPath]);
    Product::factory()->for($category)->create(['path' => $productPath]);
    Product::factory()->for($category)->create();

    Livewire::test('pages::admin.categories')
        ->call('delete', $category->id);

    expect(Category::query()->count())->toBe(0)
        ->and(Product::query()->count())->toBe(0);

    Storage::disk('public')->assertMissing($categoryPath);
    Storage::disk('public')->assertMissing($productPath);
});

test('the title is required', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test('pages::admin.categories')
        ->set('title', '')
        ->call('save')
        ->assertHasErrors(['title']);
});

test('visibility can be toggled', function () {
    $this->actingAs(User::factory()->create());

    $category = Category::factory()->create();

    Livewire::test('pages::admin.categories')
        ->call('toggleVisibility', $category->id);

    expect($category->refresh()->is_visible)->toBeFalse();
});

test('categories can be reordered', function () {
    $this->actingAs(User::factory()->create());

    $first = Category::factory()->create(['position' => 0]);
    $second = Category::factory()->create(['position' => 1]);
    $third = Category::factory()->create(['position' => 2]);

    Livewire::test('pages::admin.categories')
        ->call('sort', $third->id, 0);

    expect(Category::query()->ordered()->pluck('id')->all())
        ->toBe([$third->id, $first->id, $second->id]);

    expect($third->refresh()->position)->toBe(0)
        ->and($first->refresh()->position)->toBe(1)
        ->and($second->refresh()->position)->toBe(2);
});

test('a new category is placed last and editing keeps the position', function () {
    $this->actingAs(User::factory()->create());

    $existing = Category::factory()->create(['position' => 5]);

    Livewire::test('pages::admin.categories')
        ->call('create')
        ->set('title', 'Jaunā kategorija')
        ->call('save');

    expect(Category::query()->where('slug', 'jauna-kategorija')->sole()->position)->toBe(6);

    Livewire::test('pages::admin.categories')
        ->call('edit', $existing->id)
        ->set('title', 'Mainīts nosaukums')
        ->call('save');

    expect($existing->refresh()->position)->toBe(5);
});
