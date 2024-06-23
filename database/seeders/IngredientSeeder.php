<?php

namespace Database\Seeders;

use App\Models\Ingredient;
use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IngredientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $beef = Ingredient::create(['name' => 'Beef', 'stock' => 20000, 'stock_threshold' => 20000]);  // 20kg
        $cheese = Ingredient::create(['name' => 'Cheese', 'stock' => 5000, 'stock_threshold' => 5000]);  // 5kg
        $onion = Ingredient::create(['name' => 'Onion', 'stock' => 1000, 'stock_threshold' => 1000]);  // 1kg

        $product = Product::create(['name' => 'Burger']);

        $product->ingredients()->attach([
            $beef->id => ['quantity' => 150],
            $cheese->id => ['quantity' => 30],
            $onion->id => ['quantity' => 20],
        ]);
    }
}
