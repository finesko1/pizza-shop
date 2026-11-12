<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::create([
            'name'        => 'Ноутбук Lenovo ThinkPad X1',
            'description' => '14", 32 ГБ RAM, 1 ТБ SSD, вес 1.12 кг',
            'price'       => 189_990_00,
            'weight'      => 1_120,
            'category'    => 'Электроника',
        ]);

        Product::create([
            'name'        => 'Футболка хлопковая',
            'description' => '100% хлопок, размеры S–XXL',
            'price'       => 1_490_00,
            'weight'      => 180,
            'category'    => 'Одежда',
        ]);

        Product::factory()->count(50)->create();
    }
}
