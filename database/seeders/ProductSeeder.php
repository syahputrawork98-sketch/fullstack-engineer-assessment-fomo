<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Product::updateOrCreate(
            ['name' => 'Flash Sale Product'],
            [
                'price' => 50000,
                'stock' => 10,
            ]
        );
    }
}
