<?php

namespace Database\Factories;

use App\Enums\ProductSize;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'name' => Str::ucfirst($this->faker->words(3, true)),
            'price' => $this->faker->randomFloat(2, 20, 300),
            'size' => null,
            'is_visible' => true,
            'position' => $this->faker->numberBetween(0, 10),
        ];
    }

    public function hidden(): static
    {
        return $this->state(['is_visible' => false]);
    }

    public function discounted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'original_price' => round($attributes['price'] * 1.25, 2),
        ]);
    }

    public function sized(?ProductSize $size = null): static
    {
        return $this->state([
            'size' => $size ?? $this->faker->randomElement(ProductSize::cases()),
        ]);
    }
}
