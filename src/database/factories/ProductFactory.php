<?php

namespace Database\Factories;

use App\Enums\ProductCategoryEnum;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Создание фикстуры Product
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => ucfirst($this->faker->words(3, true)),
            'description' => $this->faker->boolean(80) ? $this->faker->paragraph(): null,
            'price' => $this->faker->numberBetween(100_00, Product::PRICE_MAX),
            'weight' => $this->faker->numberBetween(100, Product::WEIGHT_MAX),
            'category' => $this->faker->randomElement(ProductCategoryEnum::cases()),
        ];
    }
}
