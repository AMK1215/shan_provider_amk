<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\GameType;


class ShanGameTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $gameTypes = [
            ['code' => 'SHAN', 'name' => 'Shankome', 'name_mm' => 'Shankomee', 'img' => 'jackpot.png', 'status' => 1, 'order' => '15'],
            
        ];

        foreach ($gameTypes as $gameTypeData) {
            GameType::create($gameTypeData);
        }
    }
}
