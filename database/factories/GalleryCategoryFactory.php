<?php

namespace Database\Factories;

use App\Models\GalleryCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<GalleryCategory>
 */
class GalleryCategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = rtrim($this->faker->unique()->sentence(2), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'is_visible' => true,
            'position' => $this->faker->numberBetween(0, 10),
        ];
    }

    public function hidden(): static
    {
        return $this->state(['is_visible' => false]);
    }
}
