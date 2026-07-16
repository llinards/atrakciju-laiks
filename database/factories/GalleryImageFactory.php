<?php

namespace Database\Factories;

use App\Models\GalleryCategory;
use App\Models\GalleryImage;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GalleryImage>
 */
class GalleryImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        [$width, $height] = $this->faker->randomElement([
            [1600, 1200],
            [1600, 900],
            [1200, 1600],
        ]);

        return [
            'gallery_category_id' => GalleryCategory::factory(),
            'path' => 'gallery/'.Str::random(10).'.webp',
            'width' => $width,
            'height' => $height,
            'position' => $this->faker->numberBetween(0, 10),
        ];
    }
}
