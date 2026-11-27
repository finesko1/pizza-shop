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
    public function pepperoni(): static
    {
        return $this->state([
            'name' => 'Пицца Пепперони',
            'description' => 'Острая пепперони, моцарелла, томатный соус',
            'price' => 690_00,
            'weight' => 550,
            'category' => ProductCategoryEnum::Pizza,
        ]);
    }

    public function margarita(): static
    {
        return $this->state([
            'name' => 'Пицца Маргарита',
            'description' => null,
            'price' => 900_00,
            'weight' => 900,
            'category' => ProductCategoryEnum::Pizza,
        ]);
    }

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
