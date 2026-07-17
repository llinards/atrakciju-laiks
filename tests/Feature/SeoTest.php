<?php

use App\Models\Category;
use App\Models\Faq;
use App\Models\Product;

test('home page has default meta description, canonical, and open graph tags', function () {
    $response = $this->get(route('home'));

    $response->assertSuccessful();
    $response->assertSee('<meta name="description" content="'.e(config('site.description')).'"', escape: false);
    $response->assertSee('<link rel="canonical" href="'.route('home').'"', escape: false);
    $response->assertSee('<meta property="og:title" content="'.e('Atrakciju noma - '.config('app.name')).'"', escape: false);
    $response->assertSee('<meta property="og:image"', escape: false);
    $response->assertSee('<meta property="og:locale" content="lv_LV"', escape: false);
    $response->assertSee('<meta name="twitter:card" content="summary_large_image"', escape: false);
});

test('home page emits LocalBusiness and FAQPage structured data', function () {
    Faq::factory()->create(['question' => 'Vai ir iespējama piegāde?', 'answer' => 'Jā, piegādājam visā Latvijā.']);

    $response = $this->get(route('home'));

    $response->assertSee('"@type":"LocalBusiness"', escape: false);
    $response->assertSee('"@type":"FAQPage"', escape: false);
    $response->assertSee('Vai ir iespējama piegāde?');
});

test('category page derives description from the category and sets canonical', function () {
    $category = Category::factory()->create([
        'slug' => 'teltis',
        'description' => 'Teltis dažāda izmēra pasākumiem un svinībām.',
    ]);

    $response = $this->get(route('category.show', $category));

    $response->assertSuccessful();
    $response->assertSee('<meta name="description" content="Teltis dažāda izmēra pasākumiem un svinībām."', escape: false);
    $response->assertSee('<link rel="canonical" href="'.route('category.show', $category).'"', escape: false);
    $response->assertSee('"@type":"BreadcrumbList"', escape: false);
});

test('product page emits Product structured data with an offer', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create([
        'description' => 'Lieliska piepūšamā atrakcija bērnu ballītēm.',
        'price' => 50,
    ]);

    $response = $this->get(route('product.show', [$category, $product]));

    $response->assertSuccessful();
    $response->assertSee('<meta property="og:type" content="product"', escape: false);
    $response->assertSee('"@type":"Product"', escape: false);
    $response->assertSee('"priceCurrency":"EUR"', escape: false);
    $response->assertSee('<link rel="canonical" href="'.route('product.show', [$category, $product]).'"', escape: false);
});

test('sale product page canonicalizes to the category URL', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->forSale()->create();

    $response = $this->get(route('sale.show', $product));

    $response->assertSuccessful();
    $response->assertSee('<link rel="canonical" href="'.route('product.show', [$category, $product]).'"', escape: false);
});

test('long descriptions are stripped of tags and shortened to snippet length', function () {
    $category = Category::factory()->create();
    $product = Product::factory()->for($category)->create([
        'description' => '<p>'.str_repeat('Piepūšamā atrakcija ', 30).'</p>',
    ]);

    $response = $this->get(route('product.show', [$category, $product]));

    preg_match('/<meta name="description" content="([^"]*)"/', $response->getContent(), $matches);

    expect($matches[1])->not->toContain('<p>')
        ->and(mb_strlen($matches[1]))->toBeLessThanOrEqual(161);
});

test('robots.txt disallows admin paths and references the sitemap', function () {
    $response = $this->get('/robots.txt');

    $response->assertSuccessful();
    $response->assertHeader('Content-Type', 'text/plain; charset=UTF-8');
    $response->assertSee('Disallow: /dashboard');
    $response->assertSee('Sitemap: '.route('sitemap'));
});
