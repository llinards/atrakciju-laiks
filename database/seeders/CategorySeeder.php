<?php

namespace Database\Seeders;

use App\Enums\CategoryColor;
use App\Enums\ProductSize;
use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Base URL of the current production site, used to pull real product images at seed time.
     */
    private const LEGACY_SITE_URL = 'https://atrakcijulaiks.lv';

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
                    $imagePath = Arr::pull($product, 'image_path');

                    $model->products()->create([
                        ...$product,
                        'path' => $imagePath ? $this->fetchProductImage($imagePath, $product['name']) : null,
                        'position' => $productPosition,
                    ]);
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
     * Download a product image from the production site to the public disk.
     * Failures (offline, removed image) leave the product without an image
     * instead of breaking the seeder.
     */
    private function fetchProductImage(string $imagePath, string $name): ?string
    {
        $extension = pathinfo($imagePath, PATHINFO_EXTENSION) ?: 'webp';
        $path = 'products/'.Str::slug($name).'.'.$extension;

        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        $url = self::LEGACY_SITE_URL.implode('/', array_map('rawurlencode', explode('/', $imagePath)));

        try {
            $response = Http::timeout(15)->get($url);
        } catch (ConnectionException) {
            return null;
        }

        // The legacy site serves its error page with a 200 status, so also require an image content type.
        if (! $response->successful() || ! str_starts_with($response->header('Content-Type'), 'image/')) {
            return null;
        }

        Storage::disk('public')->put($path, $response->body());

        return $path;
    }

    /**
     * Piepūšamās atrakcijas mirror the production site (atrakcijulaiks.lv). Sizes are a
     * price-based estimate (130€ namiņi → mazās, 160–170€ → vidējās, 180€+ → lielās)
     * to be corrected in the admin panel once size data exists.
     *
     * @return list<array{title: string, slug: string, tagline: string, description: string, color: CategoryColor, image: string, products: list<array{name: string, price: float, original_price?: float, size?: ProductSize, image_path?: string}>}>
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
                    ['name' => 'FortNite', 'price' => 180.00, 'size' => ProductSize::Large, 'image_path' => '/media/media/upload/article/middle/FORTNITE_atrakcijas_noma_jelgava_ozolnieki_rig_430x380.webp'],
                    ['name' => 'JAUNUMS! Ķepu patruļas namiņš', 'price' => 130.00, 'size' => ProductSize::Small, 'image_path' => '/media/media/upload/article/middle/epu_bāzes_namiņa_kopskats_2026_gada_jaunums-68a1d8d3_430x380.webp'],
                    ['name' => 'JAUNUMS! Labubu', 'price' => 180.00, 'size' => ProductSize::Large, 'image_path' => '/media/media/upload/article/middle/labubu_noma_Jelgava_Riga_Sigulda-ef40a644_430x380.webp'],
                    ['name' => 'JAUNUMS! Minecraft', 'price' => 180.00, 'size' => ProductSize::Large, 'image_path' => '/media/media/upload/article/middle/Minecraft_JAUNĀKĀS_atrakcijas_kopskats-0aa4089e_430x380.webp'],
                    ['name' => 'JAUNUMS! Roblox (Robloks)', 'price' => 180.00, 'size' => ProductSize::Large, 'image_path' => '/media/media/upload/article/middle/Roblox_atrakcija_JAUNUMS_2026-d3ccc058_430x380.webp'],
                    ['name' => 'JAUNUMS! Smurfi', 'price' => 130.00, 'size' => ProductSize::Small, 'image_path' => '/media/media/upload/article/middle/Smurfi_kopskats_JAUNUMS_2026-c193f525_430x380.webp'],
                    ['name' => 'JAUNUMS! Šreks', 'price' => 180.00, 'size' => ProductSize::Large, 'image_path' => '/media/media/upload/article/middle/reka_atrakcija_uz_nomu_28317711_-d0ea021e_430x380.webp'],
                    ['name' => 'JAUNUMS! Super Mario Galaktika', 'price' => 180.00, 'size' => ProductSize::Large, 'image_path' => '/media/media/upload/article/middle/Super_Mario_Galaktika_2026_atrakcija-494b1755_430x380.webp'],
                    ['name' => 'Kaķu māja', 'price' => 160.00, 'size' => ProductSize::Medium, 'image_path' => '/media/media/upload/article/middle/IMG_20240512_1130191_430x380.webp'],
                    ['name' => 'Lāča taka', 'price' => 200.00, 'size' => ProductSize::Large, 'image_path' => '/media/media/upload/article/middle/Laca_takaatrkacijas_noma_jelgava_430x380.webp'],
                    ['name' => 'Lielais Minions', 'price' => 180.00, 'size' => ProductSize::Large, 'image_path' => '/media/media/upload/article/middle/Lielais_Minions_modelis_pārsteigs_vairākus_ballītes_viesu_8_XpfpMzF.webp'],
                    ['name' => 'Lielā ķepu patruļa', 'price' => 180.00, 'size' => ProductSize::Large, 'image_path' => '/media/media/upload/article/middle/IMG_20240501_1048311_430x380.webp'],
                    ['name' => 'Mana BALLĪTE', 'price' => 160.00, 'size' => ProductSize::Medium, 'image_path' => '/media/media/upload/article/middle/Jaunākais_modelis_MANA_Ballīte-28e20a24_430x380.webp'],
                    ['name' => 'Nemo', 'price' => 160.00, 'size' => ProductSize::Medium, 'image_path' => '/media/media/upload/article/middle/Jaunums_nemo_atrakcijas_noma_jelgava_Riga_Ozolnieki_430x380.webp'],
                    ['name' => 'Pandas namiņš', 'price' => 130.00, 'size' => ProductSize::Small, 'image_path' => '/media/media/upload/article/middle/Pandas-namins-2023_430x380.webp'],
                    ['name' => 'Pirāti', 'price' => 180.00, 'size' => ProductSize::Large, 'image_path' => '/media/media/upload/article/middle/Pirati_atrakcija_noma_jelgava_ozolniekos_REZERVE_28317711_n_nasHkQs.webp'],
                    ['name' => 'Spiderman', 'price' => 180.00, 'size' => ProductSize::Large, 'image_path' => '/media/media/upload/article/middle/Atrakciju_noma_SPIDERMAN_Jelgava_Sigulda_Riga-d3d8982a_430x380.webp'],
                    ['name' => 'Stitch (Stitčš)', 'price' => 170.00, 'size' => ProductSize::Medium, 'image_path' => '/media/media/upload/article/middle/Stitch_atrakcija_fani_sarosās_atrakciju_noma_Jelgavā_Sigu_H_zn62cHY.webp'],
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
                    ['name' => 'Gaiši pelēka zvaigzne', 'price' => 110.00, 'image_path' => '/media/media/upload/article/middle/20200417_113843-1024x498_430x380.webp'],
                    ['name' => 'Krāsaina zvaigzne', 'price' => 70.00, 'image_path' => '/media/media/upload/article/middle/68594562_2511385565586659_2990399786185654272_o-1024x683_430x380.webp'],
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
                    ['name' => 'Balta 3x6m nojume', 'price' => 96.00, 'image_path' => '/media/media/upload/article/middle/3x6m-baltas-nojumes-noma-Sigulda-un-Latvija-29474742_430x380.webp'],
                    ['name' => 'Bēša 4x8m nojume', 'price' => 148.00, 'image_path' => '/media/media/upload/article/middle/gaisi-pelekas-nojumes-noma-4x8m-1-scaled_430x380.webp'],
                    ['name' => 'Melna 3x6m nojume', 'price' => 96.00, 'image_path' => '/media/media/upload/article/middle/3x6m-PREMIUM-klases-nojumes-noma-Sigulda-Jelgava-Riga-Latvi_0hgVfdY.webp'],
                ],
            ],
        ];
    }
}
