<?php

namespace Database\Seeders;

use App\Enums\CategoryColor;
use App\Enums\ProductSize;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach ($this->categories() as $position => $category) {
            $model = Category::firstOrCreate(
                ['slug' => $category['slug']],
                [
                    'title' => $category['title'],
                    'tagline' => $category['tagline'],
                    'description' => $category['description'],
                    'color' => $category['color'],
                    'path' => $this->publishImage($category['slug'], $category['image']),
                    'position' => $position,
                ],
            );

            if ($model->products()->doesntExist()) {
                foreach ($category['products'] as $productPosition => $product) {
                    $model->products()->create([...$product, 'position' => $productPosition]);
                }
            }
        }
    }

    /**
     * Copy a bundled public image to the public disk so seeded
     * categories behave like admin-uploaded ones.
     */
    private function publishImage(string $slug, string $image): ?string
    {
        $source = public_path("images/{$image}");

        if (! File::exists($source)) {
            return null;
        }

        $path = "categories/{$slug}.png";

        if (! Storage::disk('public')->exists($path)) {
            Storage::disk('public')->put($path, File::get($source));
        }

        return $path;
    }

    /**
     * @return list<array{title: string, slug: string, tagline: string, description: string, color: CategoryColor, image: string, products: list<array{name: string, price: float, original_price?: float, size?: ProductSize}>}>
     */
    private function categories(): array
    {
        return [
            [
                'title' => 'Piepūšamās atrakcijas',
                'slug' => 'piepusamas-atrakcijas',
                'tagline' => 'Jautrībai, kustībai un bērnu priekam',
                'description' => 'Atrakcijas bērnu ballītēm, pasākumiem un aktīvai atpūtai visā Latvijā.',
                'color' => CategoryColor::Splash,
                'image' => 'category-atrakcijas.png',
                'products' => [
                    ['name' => 'Piepūšamā pils "Džungļi"', 'price' => 130.00, 'original_price' => 160.00, 'size' => ProductSize::Large],
                    ['name' => 'Šķēršļu trase "Safari"', 'price' => 150.00, 'size' => ProductSize::Large],
                    ['name' => 'Piepūšamā slidkalniņa "Titāniks"', 'price' => 140.00, 'size' => ProductSize::Large],
                    ['name' => 'Batuts "Klauns"', 'price' => 90.00, 'size' => ProductSize::Medium],
                    ['name' => 'Piepūšamā pils "Princese"', 'price' => 95.00, 'size' => ProductSize::Medium],
                    ['name' => 'Sporta arēna "Futbols"', 'price' => 100.00, 'size' => ProductSize::Medium],
                    ['name' => 'Mini batuts "Bitīte"', 'price' => 60.00, 'size' => ProductSize::Small],
                    ['name' => 'Bumbu baseins "Okeāns"', 'price' => 55.00, 'size' => ProductSize::Small],
                    ['name' => 'Piepūšamā pils "Rūķītis"', 'price' => 65.00, 'size' => ProductSize::Small],
                    ['name' => 'Šķēršļu trase "Pirāti"', 'price' => 145.00, 'size' => ProductSize::Large],
                    ['name' => 'Batuts "Varavīksne"', 'price' => 85.00, 'size' => ProductSize::Medium],
                    ['name' => 'Mini slidkalniņš "Pingvīns"', 'price' => 50.00, 'size' => ProductSize::Small],
                    ['name' => 'Piepūšamā trase "Tornado"', 'price' => 160.00, 'size' => ProductSize::Large],
                    ['name' => 'Bumbu baseins "Saulīte"', 'price' => 45.00, 'size' => ProductSize::Small],
                ],
            ],
            [
                'title' => 'Teltis',
                'slug' => 'teltis',
                'tagline' => 'Ērtam pasākumam jebkuros laikapstākļos',
                'description' => 'Teltis pasākumiem, svinībām un brīvdabas aktivitātēm jebkuros laikapstākļos.',
                'color' => CategoryColor::Brand,
                'image' => 'category-teltis.png',
                'products' => [
                    ['name' => 'Pasākumu telts 3x6 m', 'price' => 80.00],
                    ['name' => 'Pasākumu telts 4x8 m', 'price' => 120.00, 'original_price' => 150.00],
                    ['name' => 'Pasākumu telts 6x12 m', 'price' => 200.00],
                    ['name' => 'Svinību telts ar logiem 5x10 m', 'price' => 170.00],
                ],
            ],
            [
                'title' => 'Nojumes',
                'slug' => 'nojumes',
                'tagline' => 'Praktisks risinājums svinībām un pasākumiem ārā',
                'description' => 'Nojumes svinībām, tirdziņiem un pasākumiem zem klajas debess.',
                'color' => CategoryColor::Sun,
                'image' => 'category-nojumes.png',
                'products' => [
                    ['name' => 'Nojume 3x3 m', 'price' => 45.00],
                    ['name' => 'Nojume 3x6 m', 'price' => 70.00],
                    ['name' => 'Nojume 4x8 m', 'price' => 110.00],
                ],
            ],
        ];
    }
}
