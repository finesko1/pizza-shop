<?php

namespace Database\Seeders;

use App\Enums\ProductCategoryEnum;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::query()->firstOrCreate(
            ['name' => 'Пицца Маргарита'],
            [
                'description' => 'Томатный соус, моцарелла, базилик, 30 см',
                'price'       => 590_00,
                'weight'      => 520,
                'category'    => ProductCategoryEnum::Pizza,
            ]
        );

        Product::query()->firstOrCreate(
            ['name' => 'Лимонад домашний'],
            [
                'description' => 'Цитрусовый напиток, 0.5 л',
                'price'       => 190_00,
                'weight'      => 500,
                'category'    => ProductCategoryEnum::Drinks,
            ]
        );

        if (app()->environment(['local', 'testing'])) {
            Product::factory()->count(50)->create();
        }
    }
}
