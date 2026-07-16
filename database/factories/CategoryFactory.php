<?php

namespace Database\Factories;

use App\Enums\CategoryColor;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
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
            'tagline' => $this->faker->sentence(4),
            'description' => $this->faker->sentence(),
            'color' => $this->faker->randomElement(CategoryColor::cases()),
            'is_visible' => true,
            'position' => $this->faker->numberBetween(0, 10),
        ];
    }

    public function hidden(): static
    {
        return $this->state(['is_visible' => false]);
    }
}
