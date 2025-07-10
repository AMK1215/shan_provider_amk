<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ShanProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create or update the ShanKomee product
        Product::updateOrCreate(
            ['product_code' => '100200'], // Condition to find the record
            [
                'provider' => 'ShanKomee',
                'currency' => 'MMK',
                'status' => 'ACTIVATED',
                'provider_id' => 102,
                'provider_product_id' => 100200,
                'product_name' => 'shan_komee',
                'game_type' => 'CARD_GAME',
                'product_title' => 'ShanKomee',
                'short_name' => 'SKM',
                'order' => 1,
                'game_list_status' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info("ShanKomee product with code '100200' seeded successfully.");
    }
} 