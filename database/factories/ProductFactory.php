<?php

namespace Database\Factories;

use App\Enums\ProductSize;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

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
            'name' => rtrim($this->faker->sentence(3), '.'),
            'price' => $this->faker->randomFloat(2, 20, 300),
            'size' => null,
            'is_new' => false,
            'is_for_sale' => false,
            'is_visible' => true,
            'position' => $this->faker->numberBetween(0, 10),
        ];
    }

    public function hidden(): static
    {
        return $this->state(['is_visible' => false]);
    }

    public function isNew(): static
    {
        return $this->state(['is_new' => true]);
    }

    public function discounted(): static
    {
        return $this->state(fn (array $attributes): array => [
            'discount_price' => round($attributes['price'] * 0.8, 2),
        ]);
    }

    public function forSale(): static
    {
        return $this->state(fn (array $attributes): array => [
            'is_for_sale' => true,
            'sale_price' => round($attributes['price'] * 4, 2),
        ]);
    }

    public function sized(?ProductSize $size = null): static
    {
        return $this->state([
            'size' => $size ?? $this->faker->randomElement(ProductSize::cases()),
        ]);
    }
}
