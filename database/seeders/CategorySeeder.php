<?php

namespace Database\Seeders;

use App\Enums\CategoryColor;
use App\Enums\ProductSize;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Image;
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
                    'description' => $category['description'],
                    'color' => $category['color'],
                    'path' => $this->publishImage($category['slug'], $category['image']),
                    'position' => $position,
                ],
            );

            foreach ($category['products'] as $productPosition => $product) {
                $imagePath = Arr::pull($product, 'image_path');
                $gallery = Arr::pull($product, 'gallery', []);

                $data = [...$product, 'position' => $productPosition];

                // Never clear an existing image when the legacy site is unreachable.
                if ($imagePath !== null && ($path = $this->fetchProductImage($imagePath, $product['name'])) !== null) {
                    $data['path'] = $path;
                }

                // Set explicitly because DatabaseSeeder mutes the model event
                // that would otherwise generate the slug on create.
                $data['slug'] = $model->products()->where('name', $product['name'])->value('slug')
                    ?? Product::generateUniqueSlug($product['name'], $model->id);

                $productModel = $model->products()->updateOrCreate(['name' => $product['name']], $data);

                if ($productModel->images()->doesntExist()) {
                    foreach ($gallery as $galleryPosition => $galleryImagePath) {
                        $galleryPath = $this->fetchGalleryImage($galleryImagePath, $data['slug'], $galleryPosition);

                        if ($galleryPath !== null) {
                            $productModel->images()->create(['path' => $galleryPath, 'position' => $galleryPosition]);
                        }
                    }
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

        $bytes = $this->fetchLegacyImage($imagePath);

        if ($bytes === null) {
            return null;
        }

        Storage::disk('public')->put($path, $bytes);

        return $path;
    }

    /**
     * Download a gallery image and run it through the same optimize pipeline
     * as admin uploads, so seeded galleries match uploaded ones.
     */
    private function fetchGalleryImage(string $imagePath, string $slug, int $position): ?string
    {
        $path = "products/gallery/{$slug}-{$position}.webp";

        if (Storage::disk('public')->exists($path)) {
            return $path;
        }

        $bytes = $this->fetchLegacyImage($imagePath);

        if ($bytes === null) {
            return null;
        }

        $stored = Image::fromBytes($bytes)
            ->orient()
            ->cover(Product::IMAGE_WIDTH, Product::IMAGE_HEIGHT)
            ->optimize()
            ->storePubliclyAs('products/gallery', "{$slug}-{$position}.webp", 'public');

        return $stored ?: null;
    }

    /**
     * Fetch raw image bytes from the legacy site, or null when unreachable.
     */
    private function fetchLegacyImage(string $imagePath): ?string
    {
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

        return $response->body();
    }

    /**
     * Piepūšamās atrakcijas mirror the production site (atrakcijulaiks.lv). Sizes are a
     * price-based estimate (130€ namiņi → mazās, 160–170€ → vidējās, 180€+ → lielās)
     * to be corrected in the admin panel once size data exists. Detail-page content
     * (description, specs, rental prices/terms) is transcribed from the legacy
     * product pages, category by category.
     *
     * @return list<array{title: string, slug: string, description: string, color: CategoryColor, image: string, products: list<array{name: string, price: float, discount_price?: float, size?: ProductSize, is_new?: bool, description?: string, suitability_items?: list<string>, specs?: array<string, string>, rental_prices?: array<string, string>, included_items?: list<string>, rental_terms?: string, image_path?: string, gallery?: list<string>}>}>
     */
    private function categories(): array
    {
        $suitabilityLarge = [
            'Bērniem no 3 gadu vecuma',
            'Līdz 8 bērniem vienlaicīgi',
            'Vienam bērnam līdz 70 kg',
            'Bērniem līdz 160 cm augumam',
        ];

        $suitabilitySmall = [
            'Bērniem no 3 gadu vecuma',
            'Līdz 6 bērniem vienlaicīgi',
            'Vienam bērnam līdz 60 kg',
            'Bērniem līdz 160 cm augumam',
        ];

        $includedLarge = [
            'atrakcijai piemērots gaisa pūtējs;',
            'elektroapgādes pagarinātājs 25 m vai 40 m garumā;',
            'apakšklājs / pārklājs;',
            'nepieciešamais drošības aprīkojums.',
        ];

        $includedStandard = [
            'atrakcijai piemērots gaisa pūtējs;',
            'elektroapgādes pagarinātājs 40 m garumā;',
            'apakšklājs / pārklājs.',
        ];

        $clientRequirements = 'No klienta puses jānodrošina zaļā zona inventāra novietošanai. Novietojuma vietai '
            .'jābūt brīvi un ērti piekļūstamai ar vismaz 1,3 m platiem vārtiņiem, durvīm vai piebraucamo ceļu. '
            .'40 m attālumā jābūt elektroapgādes standarta slēgumam 220–240V.';

        $attendantNote = 'Korporatīvos pasākumos un sporta spēlēs pieejams atrakcijas pavadonis — mūsu darbinieks, '
            .'kurš organizē lietotāju plūsmu un pieskata, lai atrakcija tiktu ekspluatēta atbilstoši tās '
            .'lietošanas noteikumiem.';

        $delivery = 'Piegāde: 0,40 €/km. Cenā ietilpst atrakcijas atvešana, profesionāla uzstādīšana un tās '
            .'novākšana pēc pasākuma — bez slēptām papildu izmaksām!';

        $serviceLatvia = 'Apkalpojam klientus visā Latvijā, projekta piedāvājumus sagatavojam individuāli. ';

        return [
            [
                'title' => 'Piepūšamās atrakcijas',
                'slug' => 'piepusamas-atrakcijas',
                'description' => 'Atrakcijas bērnu ballītēm, pasākumiem un aktīvai atpūtai visā Latvijā.',
                'color' => CategoryColor::Splash,
                'image' => 'category-atrakcijas.png',
                'products' => [
                    [
                        'name' => 'FortNite',
                        'price' => 180.00,
                        'size' => ProductSize::Large,
                        'description' => "2023. gada sezonā vairāki klienti jautāja pēc FORTNITE! Mēs jūs sadzirdējām.\n\n"
                            .'Atrakcijā ir aizraujoša šķēršļu josla un varens pakāpienu režģis, kura uzvarētājus '
                            .'apbalvo ar garāko sortimenta slidkalniņa nobraucienu! Atrakcijas slidkalniņa jumta '
                            ."Fortnite elementu redzēs vēl tālu citi kaimiņi — tā augstums sasniedz 6,5 m.\n\n"
                            .'Atrakcijas slidkalniņa nobrauciens 500 centimetru garumā un 160 centimetru platumā '
                            .'sagādās īstu piedzīvojumu un paliks atmiņā kā vasaras superpiedzīvojums. Bērniem '
                            .'noteikti patiktu arī kariņš ar NERF pistolēm tieši šajā šķēršļotajā atrakcijā!',
                        'suitability_items' => $suitabilityLarge,
                        'specs' => [
                            'Augstums' => '6,5 m',
                            'Slidkalniņa nobrauciens' => '5,0 m garš, 1,6 m plats',
                            'Elektroapgāde' => '220–240V',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '180€',
                            'Piektdiena–svētdiena un svētku dienas' => '180€',
                            'Korporatīviem, sporta, publiskiem un bērnudārzu pasākumiem' => '290€',
                        ],
                        'included_items' => $includedStandard,
                        'rental_terms' => 'Viena nomas diena ir no plkst. 8:30 / 12:00 līdz plkst. 18:00 / 20:30. '
                            ."Nomājot vienu atrakciju divas un vairāk dienas, piemērojam atlaidi katrai nākamajai nomas dienai.\n\n"
                            .$clientRequirements."\n\n"
                            .$delivery,
                        'image_path' => '/media/media/upload/article/middle/FORTNITE_atrakcijas_noma_jelgava_ozolnieki_rig_430x380.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/FORTNITE_atrakcijas_noma_jelgava_iekspuse.webp',
                            '/media/upload/articlegal/original/FORTNITE_atrakciju_noma_28317711_Jelgav_Dobele_Riga.webp',
                            '/media/upload/articlegal/original/FORTNITE_atrakciju_noma_28317711_Jelgava_Dobele_Ozolnieki.webp',
                            '/media/upload/articlegal/original/FORTNITE_atrakcijas_noma_jelgava_ozolnieki_riga_iekspuse__1.webp',
                            '/media/upload/articlegal/original/FORTNITE_atrakcijas_noma_jelgava_ozolnieki_riga_iekspuse.webp',
                            '/media/upload/articlegal/original/FORTNITE_atrakcijas_noma_jelgava_ozolnieki_rig.webp',
                        ],
                    ],
                    [
                        'name' => 'Ķepu patruļas namiņš',
                        'price' => 130.00,
                        'size' => ProductSize::Small,
                        'is_new' => true,
                        'description' => '2026. gada nomas sezonas jaunums mazo modeļu segmentā — Ķepu patruļas bāzes namiņš!',
                        'suitability_items' => $suitabilitySmall,
                        'specs' => [
                            'Garums' => '5,7 m',
                            'Platums' => '4,9 m',
                            'Augstums' => '4,8 m',
                            'Svars' => '157 kg',
                            'Elektroapgāde' => '220–240V',
                            'Sertifikāts' => 'ISO EN14960:2013',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '130€',
                            'Piektdiena–svētdiena un svētku dienas' => '130€',
                        ],
                        'included_items' => [
                            'atrakcijai piemērots gaisa pūtējs;',
                            'elektroapgādes pagarinātājs 40 m garumā;',
                            'apakšklājs / pārklājs (zaļā vai sudraba krāsā).',
                        ],
                        'rental_terms' => 'Viena nomas diena ir no plkst. 8:00–12:00 (piegādes un montāžas laiks) '
                            ."līdz plkst. 18:00–20:30 (demontāžas laiks).\n\n"
                            .$attendantNote."\n\n"
                            .$delivery,
                        'image_path' => '/media/media/upload/article/middle/epu_bāzes_namiņa_kopskats_2026_gada_jaunums-68a1d8d3_430x380.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/epu_bāzes_namiņš_JAUNUMS-c936ad55.webp',
                            '/media/upload/articlegal/original/epu_namiņš_JAUNUMS_2026-97abf610.webp',
                            '/media/upload/articlegal/original/epu_patruļas_namiņa_iekšpuses_elementi_SKAISTI-f64be2fb.webp',
                            '/media/upload/articlegal/original/JAUNUMS_nomai_Ķepu_bāzes_namiņš_iekšpuse-32d362ee.webp',
                        ],
                    ],
                    [
                        'name' => 'Labubu',
                        'price' => 180.00,
                        'size' => ProductSize::Large,
                        'is_new' => true,
                        'description' => "💛 Labubu atrakcija ir 2026. gada TOP3 skaļākais jaunums mūsu sortimentā! 💥\n\n"
                            .'Nav jāmeklē, tas ir šeit — WOW efekts bērnu ballītei, šī atrakcija ir tieši tas, ko '
                            .'vajag! Labubu mānija ir pārņēmusi pasauli, un tagad šis stilīgais, viltīgi smaidīgais '
                            ."tēls var viesoties arī Tavā pasākumā — Jūsu pagalmā.\n\n"
                            .'Lielais Labubu modelis apvieno sevī aizraujošu šķēršļu joslu, milzīgu slīdkalniņu un '
                            .'nebeidzamu jautrību, pārvarot šķēršļu trasi, lai ātrāk nokļūtu pie slīdkalniņa. Tā ir '
                            .'ideāla izvēle tematiskajām ballītēm, kas izskatīsies izcili gan dzīvē, gan video un '
                            ."bilžu atmiņās!\n\n"
                            .'Atrakcijas slīdkalniņa nobrauciens ir iespaidīgs — 5,9 metru augstums sagādās patiesu '
                            .'adrenalīna devu un lidojuma sajūtu katram mazajam piedzīvojumu meklētājam!',
                        'suitability_items' => $suitabilityLarge,
                        'specs' => [
                            'Garums' => '9,5 m',
                            'Platums' => '4,4 m',
                            'Augstums' => '6,9 m',
                            'Svars' => '233 kg',
                            'Elektroapgāde' => '220–240V',
                            'Sertifikāts' => 'ISO EN14960:2013',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '180€',
                            'Piektdiena–svētdiena un svētku dienas' => '180€',
                            'Korporatīviem, sporta, publiskiem un bērnudārzu pasākumiem' => '290€',
                        ],
                        'included_items' => $includedLarge,
                        'rental_terms' => 'Viena nomas diena ir no plkst. 8:00 / 12:00 līdz plkst. 18:00 / 20:00 vai '
                            .'pēc atsevišķas vienošanās. Nomājot vienu atrakciju divas un vairāk dienas, piemērojam '
                            ."atlaidi katrai nākamajai nomas dienai.\n\n"
                            .$clientRequirements."\n\n"
                            .$attendantNote."\n\n"
                            .$serviceLatvia.$delivery,
                        'image_path' => '/media/media/upload/article/middle/labubu_noma_Jelgava_Riga_Sigulda-ef40a644_430x380.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/Labubu_atrakcijas_aizmugure-e9343255.webp',
                            '/media/upload/articlegal/original/LABUBU_atrakcijas_elementi_šķēršļi-78a66b11.webp',
                            '/media/upload/articlegal/original/Labubu_atrakcijas_noma-b04b69c9.webp',
                            '/media/upload/articlegal/original/Labubu_atrakcijas_noma_Riga_Jelgava_Dobele-76706cf5.webp',
                            '/media/upload/articlegal/original/Labubu_JUANUMUS_nomas_sortimentā_NOMA_28317711-efc2dd70.webp',
                        ],
                    ],
                    [
                        'name' => 'Minecraft',
                        'price' => 180.00,
                        'size' => ProductSize::Large,
                        'is_new' => true,
                        'description' => 'Minecraft piepūšamā atrakcija ir iespaidīgs un vizuāli pievilcīgs jaunums '
                            ."2026. gada nomas sezonai, kas lieliski piemērots bērnu ballītēm un tematiskajiem pasākumiem.\n\n"
                            .'Atrakcija veidota Minecraft stilistikā ar atpazīstamiem elementiem, kas rada spēles '
                            .'piedzīvojuma atmosfēru un uzreiz piesaista bērnu uzmanību. Atrakcija apvieno sevī '
                            .'vairākas aktivitātes — šķēršļu joslu, kustību zonu un lielu slīdkalniņu, nodrošinot '
                            ."patīkamu nobraucienu sajūtu un aktīvu izklaidi šķēršļu zonā.\n\n"
                            .'Bērni var pārvarēt šķēršļus, kustēties, skriet un sacensties, lai nokļūtu līdz '
                            ."slidkalniņa nobraucienam, kas ir atrakcijas galvenais elements un lielākais piedzīvojums.\n\n"
                            .'Košais dizains un Minecraft tematika padara šo modeli par centrālo aktivitāti jebkurā '
                            .'pasākumā — lieliska izvēle dzimšanas dienas ballītēm, bērnudārzu un skolu pasākumiem, '
                            .'privātiem un korporatīviem pasākumiem.',
                        'suitability_items' => $suitabilityLarge,
                        'specs' => [
                            'Garums' => '9,5 m',
                            'Platums' => '4,4 m',
                            'Augstums' => '6,9 m',
                            'Svars' => '234 kg',
                            'Elektroapgāde' => '220–240V',
                            'Sertifikāts' => 'ISO EN14960:2013',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '180€',
                            'Piektdiena–svētdiena un svētku dienas' => '180€',
                            'Korporatīviem, sporta, publiskiem un bērnudārzu pasākumiem' => '290€',
                        ],
                        'included_items' => $includedLarge,
                        'rental_terms' => 'Viena nomas diena ir no plkst. 8:00 / 12:00 līdz plkst. 18:00 / 20:00 vai '
                            .'pēc atsevišķas vienošanās. Nomājot vienu atrakciju divas un vairāk dienas, piemērojam '
                            ."atlaidi katrai nākamajai nomas dienai.\n\n"
                            .$clientRequirements."\n\n"
                            .$attendantNote."\n\n"
                            .$serviceLatvia.$delivery,
                        'image_path' => '/media/media/upload/article/middle/Minecraft_JAUNĀKĀS_atrakcijas_kopskats-0aa4089e_430x380.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/Minecraft_atrakcijas_noma-106c554b.webp',
                            '/media/upload/articlegal/original/Minecraft_JAUNUMS-d6cf5bff.webp',
                            '/media/upload/articlegal/original/JAUNUMS_2026_Minecraft_noma_-fe7e72fc.webp',
                            '/media/upload/articlegal/original/Minecraft_JAUNĀKĀS_atrakcijas_kopskats-0aa4089e.webp',
                        ],
                    ],
                    [
                        'name' => 'Roblox (Robloks)',
                        'price' => 180.00,
                        'size' => ProductSize::Large,
                        'is_new' => true,
                        'description' => 'Roblox atrakcija ir 2026. gada nomas sezonas jaunums — košs lielā izmēra '
                            .'modelis ar aizraujošu šķēršļu joslu un lielu slīdkalniņa nobraucienu, kas veidots '
                            .'iemīļotās spēles stilistikā.',
                        'suitability_items' => $suitabilityLarge,
                        'specs' => [
                            'Garums' => '9,5 m',
                            'Platums' => '4,4 m',
                            'Augstums' => '6,8 m',
                            'Svars' => '236 kg',
                            'Elektroapgāde' => '220–240V',
                            'Sertifikāts' => 'ISO EN14960:2013',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '180€',
                            'Piektdiena–svētdiena un svētku dienas' => '180€',
                            'Korporatīviem, sporta, publiskiem un bērnudārzu pasākumiem' => '290€',
                        ],
                        'included_items' => $includedLarge,
                        'rental_terms' => 'Viena nomas diena ir no plkst. 8:00 / 12:00 līdz plkst. 18:00 / 20:00 vai '
                            .'pēc atsevišķas vienošanās. Nomājot vienu atrakciju divas un vairāk dienas, piemērojam '
                            ."atlaidi katrai nākamajai nomas dienai.\n\n"
                            .$clientRequirements."\n\n"
                            .$attendantNote."\n\n"
                            .$serviceLatvia.$delivery,
                        'image_path' => '/media/media/upload/article/middle/Roblox_atrakcija_JAUNUMS_2026-d3ccc058_430x380.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/JAUNUMS_Roblox_roblokss_atrakcijas_aizmugure-3d91c72c.webp',
                            '/media/upload/articlegal/original/Roblox_lielā_atrakcija_neliks_vilties_AIZIET-722e6156.webp',
                            '/media/upload/articlegal/original/Roblox_atrakcijas_kopskats_priekšpuse-3f861d05.webp',
                            '/media/upload/articlegal/original/JAUNUMS_Roblox_atrakcijas_šķēršļu_trase-8fd2f5d7.webp',
                        ],
                    ],
                    [
                        'name' => 'Smurfi',
                        'price' => 130.00,
                        'size' => ProductSize::Small,
                        'is_new' => true,
                        'description' => '2026. gada nomas sezonas jaunums mazo modeļu segmentā — Smurfi!',
                        'suitability_items' => $suitabilitySmall,
                        'specs' => [
                            'Garums' => '5,7 m',
                            'Platums' => '4,9 m',
                            'Augstums' => '4,8 m',
                            'Svars' => '157 kg',
                            'Elektroapgāde' => '220–240V',
                            'Sertifikāts' => 'ISO EN14960:2013',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '130€',
                            'Piektdiena–svētdiena un svētku dienas' => '130€',
                        ],
                        'included_items' => [
                            'atrakcijai piemērots gaisa pūtējs;',
                            'elektroapgādes pagarinātājs 40 m garumā;',
                            'apakšklājs / pārklājs (zaļā vai sudraba krāsā).',
                        ],
                        'rental_terms' => 'Viena nomas diena ir no plkst. 8:00–12:00 (piegādes un montāžas laiks) '
                            ."līdz plkst. 18:00–20:30 (demontāžas laiks).\n\n"
                            .$attendantNote."\n\n"
                            .$delivery,
                        'image_path' => '/media/media/upload/article/middle/Smurfi_kopskats_JAUNUMS_2026-c193f525_430x380.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/smurfu_bāzes_iekšpuse_JAUNUSM_2026_gada_nomas_sezona-b35c645d.webp',
                            '/media/upload/articlegal/original/Smurfu_namiņa_iekšpuses_ELEMENTI_2026_gada_jaunums-05c42b6a.webp',
                            '/media/upload/articlegal/original/Smurfu_namiņa_slidkalna_nobrauciens_JAUNUMS-91655e58.webp',
                            '/media/upload/articlegal/original/Smurfu_namiņš_JAUNUMS_2026-74c2727e.webp',
                        ],
                    ],
                    [
                        'name' => 'Šreks',
                        'price' => 180.00,
                        'size' => ProductSize::Large,
                        'is_new' => true,
                        'description' => "Šreka atrakcija noteikti ir 2026. gada NEGAIDĪTĀKAIS jaunums mūsu sortimentā!\n\n"
                            .'Šreka piedzīvojumi ir klasika, ko mīl gan lieli, gan mazi, un tagad šis leģendārais, '
                            ."labsirdīgais zaļais milzis var viesoties arī Tavā pasākumā — Jūsu pagalmā.\n\n"
                            .'Atrakcija ir koša, ar Šreka un viņa draugu tēliem apvīta un aprīkota ar vareni lielu '
                            .'slīdkalniņa nobraucienu. Lielais Šreka purva modelis apvieno sevī aizraujošu šķēršļu '
                            .'joslu, milzīgu slīdkalniņu un nebeidzamu jautrību, pārvarot šķēršļu trasi, lai ātrāk '
                            ."nokļūtu pie lielā nobrauciena.\n\n"
                            .'Atrakcijas slīdkalniņa nobrauciens ir iespaidīgs — 5,9 metru augstums sagādās patiesu '
                            .'adrenalīna devu un lidojuma sajūtu katram mazajam piedzīvojumu meklētājam!',
                        'suitability_items' => $suitabilityLarge,
                        'specs' => [
                            'Garums' => '9,5 m',
                            'Platums' => '4,4 m',
                            'Augstums' => '6,9 m',
                            'Svars' => '233 kg',
                            'Elektroapgāde' => '220–240V',
                            'Sertifikāts' => 'ISO EN14960:2013',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '180€',
                            'Piektdiena–svētdiena un svētku dienas' => '180€',
                            'Korporatīviem, sporta, publiskiem un bērnudārzu pasākumiem' => '290€',
                        ],
                        'included_items' => $includedLarge,
                        'rental_terms' => 'Viena nomas diena ir no plkst. 8:00 / 12:00 līdz plkst. 18:00 / 20:00 vai '
                            .'pēc atsevišķas vienošanās. Nomājot vienu atrakciju divas un vairāk dienas, piemērojam '
                            ."atlaidi katrai nākamajai nomas dienai.\n\n"
                            .$clientRequirements."\n\n"
                            .$attendantNote."\n\n"
                            .$serviceLatvia.$delivery,
                        'image_path' => '/media/media/upload/article/middle/reka_atrakcija_uz_nomu_28317711_-d0ea021e_430x380.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/reka_modeļa_šķēršļu_zona_2026_gada_jaunums-438a0343.webp',
                            '/media/upload/articlegal/original/reka_piepūšamā_atrakcija-cae8da88.webp',
                            '/media/upload/articlegal/original/reka_trepes_jaunums_2026_gada_sezonā-ac85d233.webp',
                            '/media/upload/articlegal/original/reka_modeļa_šķēršļu_zona_2026_gada_jaunums_29474742-fcc3b02c.webp',
                            '/media/upload/articlegal/original/reka_atrakcija_lielais_modelis_JAUNUMS_2026-1d8e3de4.webp',
                        ],
                    ],
                    [
                        'name' => 'Super Mario Galaktika',
                        'price' => 180.00,
                        'size' => ProductSize::Large,
                        'is_new' => true,
                        'description' => 'Super Mario Galaktika atrakcija ir viens no spilgtākajiem un '
                            .'aizraujošākajiem jaunumiem mūsu sortimentā! 2026. gada aprīlī šī modeļa tematikas '
                            ."kinofilmu sāka demonstrēt arī Latvijas kinoteātros.\n\n"
                            .'Super Mario piedzīvojumi ir īsta videospēļu klasika, ko mīl gan bērni, gan pieaugušie. '
                            .'Atrakcija ir koša, ar Super Mario, Luigi un citu iemīļoto tēlu elementiem, kas rada '
                            .'īstu galaktikas piedzīvojuma sajūtu. Tā apvieno aizraujošu šķēršļu joslu, ātru kustību '
                            ."un iespaidīgu slidkalniņa nobraucienu, kas ved cauri Mario pasaules izaicinājumiem līdz lielajam finišam.\n\n"
                            .'Šī atrakcija ļauj bērniem justies kā īstiem spēles varoņiem — pārvarot šķēršļus, lecot, '
                            .'skrienot un sacenšoties, lai pirmie sasniegtu galaktikas nobraucienu. Ideāla izvēle '
                            ."tematiskām ballītēm, īpaši pēc kinofilmas noskatīšanās.\n\n"
                            .'Atrakcijas slīdkalniņa nobrauciens ir iespaidīgs — 5,7 metru garums nodrošina '
                            .'aizraujošu ātrumu, adrenalīnu un īstu kosmiskā lidojuma sajūtu!',
                        'suitability_items' => $suitabilityLarge,
                        'specs' => [
                            'Garums' => '9,5 m',
                            'Platums' => '4,4 m',
                            'Augstums' => '6,9 m',
                            'Svars' => '233 kg',
                            'Elektroapgāde' => '220–240V',
                            'Sertifikāts' => 'ISO EN14960:2013',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '180€',
                            'Piektdiena–svētdiena un svētku dienas' => '180€',
                            'Korporatīviem, sporta, publiskiem un bērnudārzu pasākumiem' => '290€',
                        ],
                        'included_items' => $includedLarge,
                        'rental_terms' => 'Viena nomas diena ir no plkst. 8:00 / 12:00 līdz plkst. 18:00 / 20:00 vai '
                            .'pēc atsevišķas vienošanās. Nomājot vienu atrakciju divas un vairāk dienas, piemērojam '
                            ."atlaidi katrai nākamajai nomas dienai.\n\n"
                            .$clientRequirements."\n\n"
                            .$attendantNote."\n\n"
                            .$serviceLatvia.$delivery,
                        'image_path' => '/media/media/upload/article/middle/Super_Mario_Galaktika_2026_atrakcija-494b1755_430x380.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/super_mario_iekšpuse-32f264f6.webp',
                            '/media/upload/articlegal/original/Super_Mario_Galaktika_JAUNA_atrakcija_2026-0669ca69.webp',
                            '/media/upload/articlegal/original/Supermario_atrakcijas_sāns-4a219380.webp',
                            '/media/upload/articlegal/original/SuperMario_atrakcijas_sānā_arī_luigi_būs-cf5c5109.webp',
                            '/media/upload/articlegal/original/SUPERMARIO_atrakcijas_noma_2026-6e671e0d.webp',
                            '/media/upload/articlegal/original/SuperMario_atrakcija_ar_pilna_cikla_servisu_visā_LV-7e220ded.webp',
                            '/media/upload/articlegal/original/Atrakcijas_trepju_kāpiens_jestrais-f69af3c3.webp',
                        ],
                    ],
                    [
                        'name' => 'Kaķu māja',
                        'price' => 160.00,
                        'size' => ProductSize::Medium,
                        'description' => "Nu ko, kaķu draugi — īstais modelis! Iepriecini bērnus, sazinoties ar mums!\n\n"
                            .'Atrakcijā ir plaša lēkājamā zona, šķēršļu josla un pieklājīgi garš slidkalniņa '
                            .'nobrauciens. Košās krāsas rada patīkamu sajūtu kaķu mīļiem, un atrakciju rotā dažādi '
                            .'kaķu silueti, radot īpaši patīkamu atmosfēru.',
                        'suitability_items' => $suitabilityLarge,
                        'specs' => [
                            'Garums' => '7,3 m',
                            'Platums' => '5,5 m',
                            'Augstums' => '5,4 m',
                            'Slidkalniņa nobrauciens' => '4,2 m garš, 1,2 m plats',
                            'Elektroapgāde' => '220–240V',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '160€',
                            'Piektdiena–svētdiena un svētku dienas' => '160€',
                            'Korporatīviem, sporta, publiskiem un bērnudārzu pasākumiem' => '260€',
                        ],
                        'included_items' => $includedStandard,
                        'rental_terms' => 'Viena nomas diena ir no plkst. 9:30 / 10:00 līdz plkst. 19:00 / 20:30. '
                            ."Nomājot vienu atrakciju divas un vairāk dienas, piemērojam atlaidi katrai nākamajai nomas dienai.\n\n"
                            .$attendantNote."\n\n"
                            .$delivery,
                        'image_path' => '/media/media/upload/article/middle/IMG_20240512_1130191_430x380.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/kaku_maja_atrakcijas_noma_28317711.webp',
                            '/media/upload/articlegal/original/kaku_maja_modelis_atrakcijas_noma_jelgava_dobele_iekspuse.webp',
                            '/media/upload/articlegal/original/kaku_maja_atrakcija.webp',
                            '/media/upload/articlegal/original/kaku_maja_modelis_atrakcijas_noma_jelgava_dobele.webp',
                            '/media/upload/articlegal/original/kaku_maja_modelis_atrakcijas_noma_jelgav.webp',
                        ],
                    ],
                    [
                        'name' => 'Lāča taka',
                        'price' => 200.00,
                        'size' => ProductSize::Large,
                        'description' => 'Lāča takas modelis ir veidots tā, lai tas radītu sacensību garu un '
                            ."stafetes atrakcijas piedzīvojuma prieku.\n\n"
                            .'Sākotnēji ir jāizlien cauri arkām, jāpārvar šķēršļu zona, tad, nonākot pie kāpšļiem, '
                            ."jāuzrāpjas klintī, no kuras ir liels, plats slidkalniņa nobrauciens.\n\n"
                            .'Ļoti interesants un pieprasīts modelis ģimenēm, kuras ar sportu ir uz Tu, sporta '
                            .'spēlēm un uzņēmumu, jauniešu un bērnu saliedēšanās pasākumiem.',
                        'suitability_items' => $suitabilityLarge,
                        'specs' => [
                            'Slidkalniņa nobrauciens' => '3,6 m garš, 1,8 m plats',
                            'Elektroapgāde' => '220–240V',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '200€',
                            'Piektdiena–svētdiena un svētku dienas' => '200€',
                        ],
                        'included_items' => $includedStandard,
                        'rental_terms' => 'Viena nomas diena ir no plkst. 9:00 līdz plkst. 20:00. Nomājot vienu '
                            ."atrakciju divas un vairāk dienas, nomas maksa nākamajai dienai ir 50% no sākuma cenas.\n\n"
                            .$delivery,
                        'image_path' => '/media/media/upload/article/middle/Laca_takaatrkacijas_noma_jelgava_430x380.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/Laca_takaatrkacijas_noma_jelgav.webp',
                            '/media/upload/articlegal/original/Laca_taka_atrkacijas_noma_iekspuse.webp',
                            '/media/upload/articlegal/original/Laca_takas_atrakcijas_skerslu_zona.webp',
                            '/media/upload/articlegal/original/Laca_taka_atrkacijas_noma_iekspuse__1.webp',
                            '/media/upload/articlegal/original/Laca_taka_atrkacijas_noma_jelgava_dobele.webp',
                        ],
                    ],
                    [
                        'name' => 'Lielais Minions',
                        'price' => 180.00,
                        'size' => ProductSize::Large,
                        'description' => "Minionu trase — lielais piedzīvojums sākas šeit! 💥\n\n"
                            .'Ja meklē WOW efektu bērnu ballītei, šī atrakcija to garantē! Koša, saulaini dinamiska '
                            .'un pilna pozitīvu emociju un aktivitāšu — Lielais Minions modelis apvieno sevī '
                            .'šķēršļus, milzīgu slīdkalniņu un jautrību, kas nebeidzas līdz pat vakaram. Lieliska '
                            ."iespēja tematiskai ballītei.\n\n"
                            .'Ar spilgtu un smieklīgu minionu dizainu tā uzreiz piesaista bērnu uzmanību un kļūst '
                            .'par pasākuma centrālo fokusu! Atrakcijas slidkalniņa nobrauciens 590 centimetru garumā '
                            .'un 135 centimetru platumā sagādās īstu piedzīvojuma sajūtu.',
                        'suitability_items' => $suitabilityLarge,
                        'specs' => [
                            'Garums' => '9,5 m',
                            'Platums' => '4,4 m',
                            'Augstums' => '5,9 m',
                            'Svars' => '240 kg',
                            'Elektroapgāde' => '220–240V',
                            'Sertifikāts' => 'ISO EN14960:2013',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '180€',
                            'Piektdiena–svētdiena un svētku dienas' => '180€',
                            'Korporatīviem, sporta, publiskiem un bērnudārzu pasākumiem' => '290€',
                        ],
                        'included_items' => $includedStandard,
                        'rental_terms' => 'Viena nomas diena ir no plkst. 8:30 / 12:00 līdz plkst. 18:00 / 20:30. '
                            ."Nomājot vienu atrakciju divas un vairāk dienas, piemērojam atlaidi katrai nākamajai nomas dienai.\n\n"
                            .$clientRequirements."\n\n"
                            .$attendantNote."\n\n"
                            .$delivery,
                        'image_path' => '/media/media/upload/article/middle/Lielais_Minions_modelis_pārsteigs_vairākus_ballītes_viesu_8_XpfpMzF.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/2025.webp',
                            '/media/upload/articlegal/original/Jaunums_Lielais_Minions_95m_garš_modelis_sānu_skats-c4c38c41.webp',
                            '/media/upload/articlegal/original/2025_0xWrmuo.webp',
                        ],
                    ],
                    [
                        'name' => 'Lielā ķepu patruļa',
                        'price' => 180.00,
                        'size' => ProductSize::Large,
                        'description' => 'Čeiss, Rabls, Zooma un citi suņu patruļas dalībnieki ir topā? Izcila '
                            .'izvēle tematiskās ballītes noskaņās — Raiders visus izkomandēs, un jautrība būs visu '
                            ."dienu.\n\n"
                            .'Atrakcijas slidkalniņa nobrauciens 430 centimetru garumā un 140 centimetru platumā '
                            .'sagādās īstu piedzīvojumu un paliks atmiņā kā vasaras superpiedzīvojums. Atrakcijas '
                            .'sāni ir tematiskās ballītes sapnis — papildus galvenajai funkcijai būs arī milzīga '
                            ."fotosiena ar ķepu patruļas komandu!\n\n"
                            .'Atrakcijā ir izaicinoša šķēršļu josla un vareni liels slidkalniņa nobrauciens. Pirmo '
                            .'reizi bērniem braucot lejā, noteikti aizrausies elpa un būs "vau" efekts.',
                        'suitability_items' => $suitabilityLarge,
                        'specs' => [
                            'Garums' => '9,5 m',
                            'Platums' => '4,5 m',
                            'Augstums' => '5,8 m',
                            'Slidkalniņa nobrauciens' => '4,3 m garš, 1,4 m plats',
                            'Elektroapgāde' => '220–240V',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '180€',
                            'Piektdiena–svētdiena un svētku dienas' => '180€',
                        ],
                        'included_items' => $includedStandard,
                        'rental_terms' => 'Viena nomas diena ir no plkst. 9:00 līdz plkst. 20:00. Nomājot vienu '
                            ."atrakciju divas un vairāk dienas, nomas maksa nākamajai dienai ir 50% no sākuma cenas.\n\n"
                            .$delivery,
                        'image_path' => '/media/media/upload/article/middle/IMG_20240501_1048311_430x380.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/Sunu-patrulas-skerslu-josla-atrakcijaa.webp',
                            '/media/upload/articlegal/original/Sunu-patrulas-atrkacija-ballitem.webp',
                            '/media/upload/articlegal/original/Paw-patrol-atrakcijas-noma-Jelgava-Riga-Sigulda.webp',
                            '/media/upload/articlegal/original/Sunu-patrulas-atrakcijas-skerslu-josla.webp',
                            '/media/upload/articlegal/original/Liela_kepu_patrula_noma_Jelgava_Dobele.webp',
                            '/media/upload/articlegal/original/Liela_kepu_patrula_atrakcijas_skerslu_josla_zvani_28317711.webp',
                            '/media/upload/articlegal/original/Liela_kepu_patrula_atrakcijas_skerslu_josla_28317711.webp',
                            '/media/upload/articlegal/original/Liela_kepu_patrula_atrakcijas_noma_Jelgav.webp',
                        ],
                    ],
                    [
                        'name' => 'Mana BALLĪTE',
                        'price' => 160.00,
                        'size' => ProductSize::Medium,
                        'description' => "\"Mana ballīte\" — vieta, kur sākas īstā jautrība! 🎉\n\n"
                            .'Krāsaina, enerģiska un pilna dzīvesprieka — piepūšamā atrakcija "Mana ballīte" ir kā '
                            .'sapņu pasaule svētku dienā! Ar spilgtām krāsām, multfilmu varoņu tēliem, saldumu '
                            .'dizainiem un košu slidkalniņu šī atrakcija kļūst par jebkura pasākuma galveno bērnu '
                            ."uzmanības un enerģijas centru.\n\n"
                            .'🍭 Ideāla izvēle dzimšanas dienām, bērnudārza pasākumiem un ģimenes svētkiem! '
                            .'Slidkalniņa nobrauciena garums 4,50 metri, platums 0,90 metri.',
                        'suitability_items' => $suitabilityLarge,
                        'specs' => [
                            'Garums' => '7,3 m',
                            'Platums' => '5,5 m',
                            'Augstums' => '5,4 m',
                            'Svars' => '188 kg',
                            'Elektroapgāde' => '220–240V',
                            'Sertifikāts' => 'ISO EN14960:2013',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '160€',
                            'Piektdiena–svētdiena un svētku dienas' => '160€',
                            'Korporatīviem, sporta, publiskiem un bērnudārzu pasākumiem' => '275€',
                        ],
                        'included_items' => $includedStandard,
                        'rental_terms' => 'Viena nomas diena ir no plkst. 9:30 / 12:00 līdz plkst. 18:00 / 20:30. '
                            ."Nomājot vienu atrakciju divas un vairāk dienas, piemērojam atlaidi katrai nākamajai nomas dienai.\n\n"
                            .$attendantNote."\n\n"
                            .$delivery,
                        'image_path' => '/media/media/upload/article/middle/Jaunākais_modelis_MANA_Ballīte-28e20a24_430x380.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/Manā_ballītē_noteikti_būs_kokteiļi_rezervē_NOMA-4676e96b.webp',
                            '/media/upload/articlegal/original/Mana_ballīte_modelis_ir_saldējumu_fanu_sapnis_rezervē-fe383f3c.webp',
                            '/media/upload/articlegal/original/Mana_ballīte_atrakcijas_ieejas_zona-b04ae416.webp',
                            '/media/upload/articlegal/original/Mana_ballīte_atrakcijas_modeļa_slidklaniņš_un_kāpšļi-715315fb.webp',
                            '/media/upload/articlegal/original/Mana_ballīte_atrakcijas_šķēršļu_zona-f8847293.webp',
                            '/media/upload/articlegal/original/Mana_ballīte_atrakcijas_šķēršļu_zona_ar_Keksu-ede68490.webp',
                            '/media/upload/articlegal/original/MANA_BALLĪTE_atrakcija_no_sāniem_JAUNUMS_2025-695dd111.webp',
                            '/media/upload/articlegal/original/Jaunākais_modelis_MANA_Ballīte_no_priekšpuses_skats_1-a0d8aace.webp',
                        ],
                    ],
                    [
                        'name' => 'Nemo',
                        'price' => 160.00,
                        'size' => ProductSize::Medium,
                        'description' => 'NEMO modelī ir viena ieejas-izejas zona ērtākai bērnu pieskatīšanai. '
                            .'Atrakcijas kreisajā pusē atrodas plaša lēkājamā zona, un slidkalniņa un trepju zonas '
                            ."sānā ir šķēršļu josla.\n\n"
                            .'Liels atrakcijas bonuss — slidkalniņa nobrauciens ir atrakcijas iekšpusē, līdz ar to '
                            .'bērniem nav nepieciešams skriet pēc nobrauciena atpakaļ uz ieejas zonu.',
                        'suitability_items' => $suitabilityLarge,
                        'specs' => [
                            'Garums' => '7,3 m',
                            'Platums' => '5,5 m',
                            'Augstums' => '5,4 m',
                            'Slidkalniņa nobrauciens' => '4,6 m garš, 0,85 m plats',
                            'Elektroapgāde' => '220–240V',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '160€',
                            'Piektdiena–svētdiena un svētku dienas' => '160€',
                            'Korporatīviem, sporta, publiskiem un bērnudārzu pasākumiem' => '260€',
                        ],
                        'included_items' => $includedStandard,
                        'rental_terms' => 'Viena nomas diena ir no plkst. 9:30 / 10:00 līdz plkst. 19:00 / 20:30. '
                            ."Nomājot vienu atrakciju divas un vairāk dienas, piemērojam atlaidi katrai nākamajai nomas dienai.\n\n"
                            .$attendantNote."\n\n"
                            .$delivery,
                        'image_path' => '/media/media/upload/article/middle/Jaunums_nemo_atrakcijas_noma_jelgava_Riga_Ozolnieki_430x380.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/Jaunums_nemo_atrakcijas_noma_jelgava_Riga_Ozolnieki_Olaine.webp',
                            '/media/upload/articlegal/original/Jaunums_nemo_atrakcijas_noma_jelgava_Riga_Ozolniek.webp',
                            '/media/upload/articlegal/original/Jaunums_nemo_atrakcijas_noma_jelgava_Riga_Ozolnieki.webp',
                            '/media/upload/articlegal/original/Jaunums_nemo_atrakcijas_noma_jelgava_dobele.webp',
                            '/media/upload/articlegal/original/Jaunums_nemoatrakcijas_noma_jelgava_dobele.webp',
                            '/media/upload/articlegal/original/Jaunums_nemo_atrakcijas_noma_jelgava_dobele__1.webp',
                        ],
                    ],
                    [
                        'name' => 'Pandas namiņš',
                        'price' => 130.00,
                        'size' => ProductSize::Small,
                        'description' => 'Panda Jūsu sētā? Kāpēc gan nē? Patīkami zaļās krāsas atrakcijā ir '
                            ."lēkājamā zona ar četriem šķēršļu elementiem — lielākais no tiem ir žirafe.\n\n"
                            .'Lai nokļūtu atrakcijas slidkalniņā, iespējams izrāpties cauri šķērslim vai apskriet '
                            .'tam apkārt, tad, nonākot pie trepēm, uzrāpties augšā, lai sasniegtu slidkalniņa '
                            .'virsotni un pandas mājiņu.',
                        'suitability_items' => $suitabilitySmall,
                        'specs' => [
                            'Garums' => '5,7 m',
                            'Platums' => '4,9 m',
                            'Augstums' => '4,5 m',
                            'Elektroapgāde' => '220–240V',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '130€',
                            'Piektdiena–svētdiena un svētku dienas' => '130€',
                        ],
                        'included_items' => $includedStandard,
                        'rental_terms' => 'Viena nomas diena ir no plkst. 9:00 / 12:00 līdz plkst. 20:00. Nomājot '
                            ."vienu atrakciju divas un vairāk dienas, piemērojam atlaides.\n\n"
                            .$delivery,
                        'image_path' => '/media/media/upload/article/middle/Pandas-namins-2023_430x380.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/slidkalnina-nombraucies-atrakciju-noma-.webp',
                            '/media/upload/articlegal/original/Pandas-namins-skerslu-zona-NOMA.webp',
                            '/media/upload/articlegal/original/Pandas-namins-Atrakciju-noma-Latvija-Riga-Marupe-Sigulda-un-Jelgava.webp',
                            '/media/upload/articlegal/original/Pandas-namina-ir-ari-basketbola-groza-imitacijas-elementi.webp',
                        ],
                    ],
                    [
                        'name' => 'Pirāti',
                        'price' => 180.00,
                        'size' => ProductSize::Large,
                        'description' => 'Uz 2024. gada sezonu bija pasūtījums — kaut kas no pirātiem. Mēs nolēmām: '
                            ."ja pirāti, tad kuģim jābūt lielam!\n\n"
                            .'Karību jūras pirātu modelis ir 9,5 m garš tematiskais piedzīvojums īstiem jūrniekiem! '
                            ."Lai notiek — pirātu ballīte!\n\n"
                            .'Atrakcijas slidkalniņa nobrauciens 510 centimetru garumā un 145 centimetru platumā '
                            .'sagādās viļņu sajūtu gluži kā jūrā — kā īstiem pirātiem!',
                        'suitability_items' => $suitabilityLarge,
                        'specs' => [
                            'Garums' => '9,5 m',
                            'Slidkalniņa nobrauciens' => '5,1 m garš, 1,45 m plats',
                            'Elektroapgāde' => '220–240V',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '180€',
                            'Piektdiena–svētdiena un svētku dienas' => '180€',
                            'Korporatīviem, sporta, publiskiem un bērnudārzu pasākumiem' => '290€',
                        ],
                        'included_items' => $includedStandard,
                        'rental_terms' => 'Viena nomas diena ir no plkst. 8:30 / 12:00 līdz plkst. 18:00 / 20:30. '
                            ."Nomājot vienu atrakciju divas un vairāk dienas, piemērojam atlaidi katrai nākamajai nomas dienai.\n\n"
                            .$clientRequirements."\n\n"
                            .$delivery,
                        'image_path' => '/media/media/upload/article/middle/Pirati_atrakcija_noma_jelgava_ozolniekos_REZERVE_28317711_n_nasHkQs.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/Pirati_atrakcija_noma_jelgava_ozolniekos_REZERVE_28317711_no_sana__1.webp',
                            '/media/upload/articlegal/original/Pirati_atrakcija_noma_jelgava_ozolniekos_REZERVE_28317711_no_sana.webp',
                            '/media/upload/articlegal/original/Pirati_atrakcija_noma_jelgava_ozolniekos_REZERVE_28317711__3.webp',
                            '/media/upload/articlegal/original/pirati_atrakciju_noma_28317711_Jelgava_Dobele_Ozolnieki.webp',
                            '/media/upload/articlegal/original/Pirati_atrakcija_noma_jelgava_ozolniekos_28317711__2_.webp',
                            '/media/upload/articlegal/original/Pirati_atrakcija_noma_jelgava_ozolniekos_REZERVE_28317711__1.webp',
                            '/media/upload/articlegal/original/Pirati_atrakcija_noma_jelgava_ozolniekos_2831771.webp',
                        ],
                    ],
                    [
                        'name' => 'Spiderman',
                        'price' => 180.00,
                        'size' => ProductSize::Large,
                        'description' => 'Lēkā, skrien un izklaidējies kopā ar iemīļoto Spaidermenu! Šī atrakcija '
                            .'būs īsts trāpījums dzimšanas dienas ballītēs, bērnu pasākumos vai vasaras festivālos, '
                            ."kur bērniem vajag izlikt enerģiju un labi pavadīt laiku.\n\n"
                            .'Ar spilgtām, sarkanām krāsām, milzīgu Spiderman tēla klātbūtni un azartiski interesantu '
                            .'lēkāšanas zonu atrakcija piesaista bērnu uzmanību no pirmā acu skatiena. Lec iekšā '
                            ."atrakcijā un sajūties kā varonis — SPAIDERMENS!\n\n"
                            .'Atrakcijas slidkalniņa nobrauciens 590 centimetru garumā un 140 centimetru platumā '
                            .'sagādās piedzīvojumu sajūtu — gluži kā pilsētā, kurā Spaidermens, pārvietojoties pa '
                            .'māju jumtiem, izšauj savus tīklus!',
                        'suitability_items' => $suitabilityLarge,
                        'specs' => [
                            'Garums' => '9,5 m',
                            'Platums' => '4,4 m',
                            'Augstums' => '6,6 m',
                            'Svars' => '233 kg',
                            'Elektroapgāde' => '220–240V',
                            'Sertifikāts' => 'ISO EN14960:2013',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '180€',
                            'Piektdiena–svētdiena un svētku dienas' => '180€',
                            'Korporatīviem, sporta, publiskiem un bērnudārzu pasākumiem' => '290€',
                        ],
                        'included_items' => $includedStandard,
                        'rental_terms' => 'Viena nomas diena ir no plkst. 8:30 / 12:00 līdz plkst. 18:00 / 20:30. '
                            ."Nomājot vienu atrakciju divas un vairāk dienas, piemērojam atlaidi katrai nākamajai nomas dienai.\n\n"
                            .$clientRequirements."\n\n"
                            .$attendantNote."\n\n"
                            .$delivery,
                        'image_path' => '/media/media/upload/article/middle/Atrakciju_noma_SPIDERMAN_Jelgava_Sigulda_Riga-d3d8982a_430x380.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/SPIDERMAN_atrakcija_sānu_daļa-726a696c.webp',
                            '/media/upload/articlegal/original/SPIDERMAN_slidkalniņš_viens_no_lielākajiem_sortimentā-c7540f46.webp',
                            '/media/upload/articlegal/original/SPIDERMAN_atrakcijas_kāpšļu_daļa_vai_tiksi_augšā-bdf827c0.webp',
                            '/media/upload/articlegal/original/SPIDERMAN_atrakcijas_Šķēršļu_zona_vai_Vēlies_savā_ballītē-b491974a.webp',
                            '/media/upload/articlegal/original/Spiderman_ATRAKCIJAS_šķēršļu_josla_ar_arku-3ad8161b.webp',
                            '/media/upload/articlegal/original/Spiderman_atrakcijas_šķēršļu_joslas_ieeja-f1dc60cf.webp',
                            '/media/upload/articlegal/original/Spiderman_atrakcijas_sāni_īsti_foto_stūrīša_elementi-e03fd5a5.webp',
                            '/media/upload/articlegal/original/SPIDERMAN_atrakcijas_priekšpuse_Noma_Jelgavā_Siguldā_Riga_Do_ZN80Jpq.webp',
                            '/media/upload/articlegal/original/Spaidermena_atrakcija_pieejama_NOMAI-18c92ed0.webp',
                            '/media/upload/articlegal/original/Atrakciju_noma_SPIDERMAN_Jelgava_Sigulda_Riga-d3d8982a.webp',
                        ],
                    ],
                    [
                        'name' => 'Stitch (Stitčš)',
                        'price' => 170.00,
                        'size' => ProductSize::Medium,
                        'description' => 'JAUNUMS mūsu nomas sortimentā — speciāli pasūtīts un dizainēts pēc '
                            ."klientu ieteikuma.\n\n"
                            .'Piepūšamā atrakcija "Stitch" jeb Stitčš ir veidota, iedvesmojoties no populārās '
                            .'animācijas filmas "Lilo & Stitch". Šī modeļa krāšņo krāsu paleti iezīmē bērniem īpaši '
                            ."pievilcīgās citplanētieša Stitch zilās krāsas.\n\n"
                            .'Atrakcijai ir viena ieejas-izejas zona, lai būtu vieglāk kontrolēt bērnu plūsmu. '
                            .'Atrakcijas sānu daļas un to Stitch dizaina attēli neliks vilties, sagādājot atbilstošu '
                            .'atmosfēru Stitch mīļotājiem. Slidkalniņa nobrauciena garums 4,40 metri, platums 0,85 metri.',
                        'suitability_items' => $suitabilityLarge,
                        'specs' => [
                            'Garums' => '6,8 m',
                            'Platums' => '5,5 m',
                            'Augstums' => '6,8 m',
                            'Svars' => '196 kg',
                            'Elektroapgāde' => '220–240V',
                            'Sertifikāts' => 'ISO EN14960:2013',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '170€',
                            'Piektdiena–svētdiena un svētku dienas' => '170€',
                            'Korporatīviem, sporta, publiskiem un bērnudārzu pasākumiem' => '275€',
                        ],
                        'included_items' => [
                            'atrakcijai piemērots gaisa pūtējs;',
                            'elektroapgādes pagarinātājs 40 m garumā;',
                            'apakšklājs / pārklājs;',
                            'drošības aprīkojums.',
                        ],
                        'rental_terms' => 'Viena nomas diena ir aptuveni no plkst. 9:00 / 12:00 līdz plkst. '
                            .'17:00 / 20:30. Nomājot vienu atrakciju divas un vairāk dienas, piemērojam atlaidi '
                            ."katrai nākamajai nomas dienai.\n\n"
                            .$attendantNote."\n\n"
                            .$delivery,
                        'image_path' => '/media/media/upload/article/middle/Stitch_atrakcija_fani_sarosās_atrakciju_noma_Jelgavā_Sigu_H_zn62cHY.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/JAUNUMS_Stitcha_atrakcija_mūsu_nomas_sortimentā_Rezervē_2831_fNusU2v.webp',
                            '/media/upload/articlegal/original/stitch_stitčš_toma_noma_JELGAVĀ_28317711-45e520f2.webp',
                            '/media/upload/articlegal/original/2025_2DswW6M.webp',
                            '/media/upload/articlegal/original/Slidkalniņa_trepju_zona_uzkāps_dažāda_vecuma_bērni-82bdee5e.webp',
                            '/media/upload/articlegal/original/SBatutu_noma_Siguldā_un_Jelgavā-9c9e7b3c.webp',
                            '/media/upload/articlegal/original/Atrakciju_noma_Sigulda_Jelgavā_Dobelē_Olainē_Krimuldā_Raganā_OnLBZAR.webp',
                            '/media/upload/articlegal/original/Stitcha_atrakcijas_Piramīda_un_lēkājamā_zona-0f53ad2e.webp',
                            '/media/upload/articlegal/original/Stitch_atrakcija_fani_sarosās_atrakciju_noma_Jelgavā_Siguldā_n4MI7PV.webp',
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Teltis',
                'slug' => 'teltis',
                'description' => 'Teltis pasākumiem, svinībām un brīvdabas aktivitātēm jebkuros laikapstākļos.',
                'color' => CategoryColor::Brand,
                'image' => 'category-teltis.png',
                'products' => [
                    [
                        'name' => 'Gaiši pelēka zvaigzne',
                        'price' => 110.00,
                        'description' => "Rekomendējama dažāda veida brīvdabas pasākumiem.\n\n"
                            .'Īpaši piemērota sporta pasākumiem, bērnu ballītēm un neformāliem svētkiem. '
                            ."Savu pielietojumu atradīs arī kāzu formātā. Ieejas šķautnes augstums sasniedz 210 cm.\n\n"
                            .'Telts izgatavota no izturīga 510 g/m² PVC materiāla, ūdens necaurlaidīga. '
                            .'Iespējams papildus aprīkot ar gaismas virtenēm.',
                        'specs' => [
                            'Izmēri' => 'Diametrs 16 m, spices augstums 5,21 m',
                            'Montāža' => 'Enkurstieņu stiprināšana gruntī, zaļajā zonā',
                            'Cilvēku skaits' => 'Stāvvietas līdz 40, sēdvietas ap 35–45',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '110€',
                            'Piektdiena–svētdiena' => '110€',
                        ],
                        'included_items' => [
                            'telts soma ar 3-daļīgu mastu;',
                            'enkurstieņi.',
                        ],
                        'rental_terms' => 'Viena nomas diena ir no plkst. 9:00 līdz plkst. 20:00. '
                            .'Nomājot telti divas un vairāk dienas, katrai nākamajai dienai tiek piemērota atlaide. '
                            .'Piegāde: 0,40 €/km. Cenā ietilpst telts atvešana, profesionāla uzstādīšana un tās '
                            .'novākšana pēc pasākuma — bez slēptām papildu izmaksām!',
                        'image_path' => '/media/media/upload/article/middle/20200417_113843-1024x498_430x380.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/Toma_Noma_zvaigzne_Ozolniekos__Jelgavas_nov_-d3e6c021554e1a9_lvnfp6r.webp',
                            '/media/upload/articlegal/original/kazu_telts_noma_zvaigzne_toma_noma-8391dfd996966407273caabc82ec7704.webp',
                            '/media/upload/articlegal/original/thumb_599_default_big-838810156ee8e57e36343877f43bcb65-1.webp',
                            '/media/upload/articlegal/original/balta_telts_zvaigzne_noma_jelgava_toma_noma-20ec41df118aa820_yFtBbAj.webp',
                        ],
                    ],
                    [
                        'name' => 'Krāsaina zvaigzne',
                        'price' => 70.00,
                        'description' => 'Rekomendējama dažāda veida pasākumiem. Īpaši piemērota sporta pasākumiem, '
                            .'bērnu ballītēm un neformāliem svētkiem. Savu pielietojumu atradīs arī kāzu formātā '
                            ."brīvā dabā. Ieejas šķautnes augstums sasniedz 210 cm.\n\n"
                            .'Telts izgatavota no izturīga 510 g/m² PVC materiāla.',
                        'specs' => [
                            'Izmēri' => '16 m x 16 m, spices augstums 5,21 m',
                            'Montāža' => 'Enkurstieņu stiprināšana gruntī',
                            'Cilvēku skaits' => 'Līdz 40',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '70€',
                            'Piektdiena–svētdiena' => '70€',
                        ],
                        'included_items' => [
                            'telts soma ar 3-daļīgu mastu;',
                            'enkurstieņi.',
                        ],
                        'rental_terms' => 'Viena nomas diena ir no plkst. 9:00 līdz plkst. 20:00. '
                            .'Nomājot telti divas un vairāk dienas, nomas maksa nākamajai dienai ir 50% no sākuma cenas. '
                            .'Piegāde: 0,40 €/km. Cenā ietilpst telts atvešana, profesionāla uzstādīšana un tās '
                            .'novākšana pēc pasākuma — bez slēptām papildu izmaksām!',
                        'image_path' => '/media/media/upload/article/middle/68594562_2511385565586659_2990399786185654272_o-1024x683_430x380.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/20106456_1938815326364725_3212320019229190984_n.webp',
                            '/media/upload/articlegal/original/zvaigzne.webp',
                            '/media/upload/articlegal/original/Grilfests_2018_Lucavsala__Riga4-b76f67ef1cdd9c412f71ca1ea5b686ae-1.webp',
                            '/media/upload/articlegal/original/Grilfests_2018_Lucavsala__Riga5-eae94f72fd051ed7cecd7d3e366c27f4.webp',
                        ],
                    ],
                ],
            ],
            [
                'title' => 'Nojumes',
                'slug' => 'nojumes',
                'description' => 'Nojumes svinībām, tirdziņiem un pasākumiem zem klajas debess.',
                'color' => CategoryColor::Sun,
                'image' => 'category-nojumes.png',
                'products' => [
                    [
                        'name' => 'Balta 3x6m nojume',
                        'price' => 96.00,
                        'description' => 'Kvalitatīvas, ātri saliekamas PREMIUM Pop-Up nojumes būs lieliska pajumte '
                            ."svētku galdam, prezentācijas laikā vai viesībās.\n\n"
                            .'Nojumes ērti aprīkojamas ar caurredzamiem PVC logiem, sienām un durvīm. Iespējams '
                            .'savienot kopā, palielinot noseguma platību. Nojume ērti pārvietojama transporta somā, '
                            .'kas aprīkota ar transporta riteņiem.',
                        'specs' => [
                            'Izmēri' => '3 m x 6 m, maksimālais ieejas augstums 2,1 m',
                            'Augstums' => 'Regulējams',
                            'Pielietojums' => 'Pasākumi, prezentācijas, viesības, tirdzniecības zonas',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '96€',
                            'Piektdiena–svētdiena' => '96€',
                        ],
                        'included_items' => [
                            '90 kg atsvari;',
                            '4 atsaites ar zemskares / atbalsta mietiņiem;',
                            'transporta soma ar riteņiem.',
                        ],
                        'rental_terms' => 'Piegāde: 0,40 €/km. Cenā ietilpst nojumes atvešana, profesionāla '
                            .'uzstādīšana un tās novākšana pēc pasākuma — bez slēptām papildu izmaksām!',
                        'image_path' => '/media/media/upload/article/middle/3x6m-baltas-nojumes-noma-Sigulda-un-Latvija-29474742_430x380.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/3x6m-baltas-nojumes-noma-Sigulda-un-Latvija-29474742.webp',
                            '/media/upload/articlegal/original/tests_nojumes.webp',
                            '/media/upload/articlegal/original/Vasaras_volly_finals_nojumes_noma_28317711-TOMS.webp',
                            '/media/upload/articlegal/original/baltas-nojumes-noma-3x6m-visa-Laytvija-Jelgava-sigulda-875x1024.webp',
                        ],
                    ],
                    [
                        'name' => 'Bēša 4x8m nojume',
                        'price' => 148.00,
                        'description' => 'Kvalitatīvas, ātri saliekamas Pop-Up nojumes būs lieliska pajumte svētku '
                            ."galdam, prezentācijas laikā vai viesībās.\n\n"
                            .'Nojumes ērti aprīkojamas ar caurredzamiem PVC logiem, sienām un durvīm. Iespējams '
                            .'savienot kopā, palielinot noseguma platību. Nojume ērti pārvietojama transporta somā, '
                            .'kas aprīkota ar transporta riteņiem.',
                        'specs' => [
                            'Izmēri' => '4 m x 8 m, maksimālais ieejas augstums 2,1 m',
                            'Augstums' => 'Regulējams',
                            'Pielietojums' => 'Pasākumi, prezentācijas, viesības, tirdzniecības zonas',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '148€',
                            'Piektdiena–svētdiena' => '148€',
                        ],
                        'included_items' => [
                            '120 kg atsvari;',
                            'atsaites un atsaišu mietiņi;',
                            'transporta soma ar riteņiem.',
                        ],
                        'rental_terms' => 'Piegāde: 0,40 €/km. Cenā ietilpst nojumes atvešana, profesionāla '
                            .'uzstādīšana un tās novākšana pēc pasākuma — bez slēptām papildu izmaksām!',
                        'image_path' => '/media/media/upload/article/middle/gaisi-pelekas-nojumes-noma-4x8m-1-scaled_430x380.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/WhatsApp-Image-2021-04-19-at-22.webp',
                            '/media/upload/articlegal/original/Vasaras_volly_finals_nojumes_noma_28317711-TOMS_ZAe84UY.webp',
                            '/media/upload/articlegal/original/gaisi-pelekas-nojumes-noma-4x8m-Latvija-Jelgava.webp',
                            '/media/upload/articlegal/original/atsvari-komplekta-nojumem.webp',
                        ],
                    ],
                    [
                        'name' => 'Melna 3x6m nojume',
                        'price' => 96.00,
                        'description' => 'Kvalitatīvas, ātri saliekamas Pop-Up nojumes būs lieliska pajumte svētku '
                            ."galdam, prezentācijas laikā vai viesībās.\n\n"
                            .'Nojumes ērti aprīkojamas ar caurredzamiem PVC logiem, sienām un durvīm. Iespējams '
                            .'savienot kopā, palielinot noseguma platību. Nojume ērti pārvietojama transporta somā, '
                            .'kas aprīkota ar transporta riteņiem.',
                        'specs' => [
                            'Izmēri' => '3 m x 6 m, maksimālais ieejas augstums 2,1 m',
                            'Augstums' => 'Regulējams',
                            'Pielietojums' => 'Pasākumi, prezentācijas, viesības, tirdzniecības zonas',
                        ],
                        'rental_prices' => [
                            'Pirmdiena–ceturtdiena' => '96€',
                            'Piektdiena–svētdiena' => '96€',
                        ],
                        'included_items' => [
                            '120 kg atsvari;',
                            'atsaites un atsaišu mietiņi;',
                            'transporta soma ar riteņiem.',
                        ],
                        'rental_terms' => 'Viena nomas diena ir no plkst. 8:00 līdz plkst. 21:00. Nomas maksā nav '
                            .'iekļautas montāžas un demontāžas izmaksas — 20 EUR par vienu nojumi. Nomājot vienu '
                            .'nojumi divas un vairāk dienas, nomas maksa nākamajai dienai ir 50% no sākuma cenas. '
                            .'Piegāde: 0,40 €/km.',
                        'image_path' => '/media/media/upload/article/middle/3x6m-PREMIUM-klases-nojumes-noma-Sigulda-Jelgava-Riga-Latvi_0hgVfdY.webp',
                        'gallery' => [
                            '/media/upload/articlegal/original/melnas-nojumes-noma-28317711-1-scaled.webp',
                            '/media/upload/articlegal/original/melnas-nojumes-telts-noma-ar-sienam.webp',
                            '/media/upload/articlegal/original/melnas-nojumes-telts-noma-jelgava-sigulda.webp',
                            '/media/upload/articlegal/original/melnas-nojumes-noma.webp',
                        ],
                    ],
                ],
            ],
        ];
    }
}
