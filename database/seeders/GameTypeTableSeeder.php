<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\GameType;
class GameTypeTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $gameTypes = [
            ['code' => 'SLOT', 'name' => 'Slot', 'name_mm' => 'Slot', 'img' => 'default.png', 'status' => 1, 'order' => '1'],
            ['code' => 'LIVE_CASINO', 'name' => 'Live Casino', 'name_mm' => 'Live Casino', 'img' => 'default.png', 'status' => 1, 'order' => '2'],
            ['code' => 'SPORT_BOOK', 'name' => 'Sport Book', 'name_mm' => 'Sport Book', 'img' => 'default.png', 'status' => 1, 'order' => '3'],
            ['code' => 'VIRTUAL_SPORT', 'name' => 'Virtual Sport', 'name_mm' => 'Virtual Sport', 'img' => 'default.png', 'status' => 1, 'order' => '4'],
            ['code' => 'LOTTERY', 'name' => 'Lottery', 'name_mm' => 'Lottery', 'img' => 'default.png', 'status' => 1, 'order' => '5'],
            ['code' => 'QIPAI', 'name' => 'Qipai', 'name_mm' => 'Qipai', 'img' => 'default.png', 'status' => 1, 'order' => '6'],
            ['code' => 'P2P', 'name' => 'P2P', 'name_mm' => 'P2P', 'img' => 'default.png', 'status' => 1, 'order' => '7'],
            ['code' => 'FISHING', 'name' => 'Fishing', 'name_mm' => 'Fishing', 'img' => 'default.png', 'status' => 1, 'order' => '8'],
            ['code' => 'COCK_FIGHTING', 'name' => 'Cock Fighting', 'name_mm' => 'Cock Fighting', 'img' => 'default.png', 'status' => 1, 'order' => '9'],
            ['code' => 'BONUS', 'name' => 'Bonus', 'name_mm' => 'Bonus', 'img' => 'default.png', 'status' => 1, 'order' => '10'],
            ['code' => 'ESPORT', 'name' => 'ESport', 'name_mm' => 'ESport', 'img' => 'default.png', 'status' => 1, 'order' => '11'],
            ['code' => 'POKER', 'name' => 'Poker', 'name_mm' => 'Poker', 'img' => 'default.png', 'status' => 1, 'order' => '12'],
            ['code' => 'OTHERS', 'name' => 'Others', 'name_mm' => 'Others', 'img' => 'default.png', 'status' => 1, 'order' => '13'],
            ['code' => 'LIVE_CASINO_PREMIUM', 'name' => 'Live Casino Premium', 'name_mm' => 'Live Casino Premium', 'img' => 'default.png', 'status' => 1, 'order' => '14'],
        ];

        foreach ($gameTypes as $gameTypeData) {
            GameType::create($gameTypeData);
        }
    }

}
