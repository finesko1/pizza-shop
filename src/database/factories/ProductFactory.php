<?php

namespace Database\Factories;

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
            'name' => ucfirst($this->faker->words(3, true)),
            'description' => $this->faker->boolean(80) ? $this->faker->paragraph(): null,
            'price' => $this->faker->numberBetween(100_00, 100_000_00),
            'weight' => $this->faker->numberBetween(50, 50_000),
            'category' => $this->faker->randomElement([
                'Электроника', 'Одежда', 'Продукты', 'Мебель', 'Книги'
            ]),
        ];
    }
}
