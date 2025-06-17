<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class GameTypeProductTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $data = [
            // id, game_type, product_title from SQL dump
            ['product_id' => 1, 'game_type_id' => 1, 'image' => 'pragmatic_play.png', 'rate' => 1.0000],
            ['product_id' => 2, 'game_type_id' => 14, 'image' => 'PragmaticPlay.png', 'rate' => 1.0000],
            ['product_id' => 3, 'game_type_id' => 4, 'image' => 'pragmatic_play.png', 'rate' => 1.0000],
            ['product_id' => 4, 'game_type_id' => 2, 'image' => 'PragmaticPlay.png', 'rate' => 1.0000],

            ['product_id' => 5, 'game_type_id' => 1, 'image' => 'PG_Soft.png', 'rate' => 1.0000],
            ['product_id' => 6, 'game_type_id' => 1, 'image' => 'Live22.png', 'rate' => 1.0000],

            ['product_id' => 7, 'game_type_id' => 1, 'image' => 'jili.png', 'rate' => 1.0000], // slot
            ['product_id' => 8, 'game_type_id' => 8, 'image' => 'jili.png', 'rate' => 1.0000], // fishing
            ['product_id' => 9, 'game_type_id' => 2, 'image' => 'jili.png', 'rate' => 1.0000], // casino
            ['product_id' => 10, 'game_type_id' => 12, 'image' => 'jili.png', 'rate' => 1.0000], // poker

            ['product_id' => 11, 'game_type_id' => 1, 'image' => 'cq9.png', 'rate' => 1.0000],
            ['product_id' => 12, 'game_type_id' => 8, 'image' => 'cq9.png', 'rate' => 1.0000],

            ['product_id' => 13, 'game_type_id' => 1, 'image' => 'jdb.png', 'rate' => 1.0000],
            ['product_id' => 14, 'game_type_id' => 8, 'image' => 'jdb.png', 'rate' => 1.0000],
            ['product_id' => 15, 'game_type_id' => 13, 'image' => 'jdb.png', 'rate' => 1.0000],
            ['product_id' => 16, 'game_type_id' => 1, 'image' => 'PlayStar.png', 'rate' => 1.0000],

            ['product_id' => 17, 'game_type_id' => 1, 'image' => 'joker.png', 'rate' => 1.0000],
            ['product_id' => 18, 'game_type_id' => 13, 'image' => 'Joker.png', 'rate' => 1.0000],
            ['product_id' => 19, 'game_type_id' => 8, 'image' => 'joker_fishing.png', 'rate' => 1.0000],
            ['product_id' => 20, 'game_type_id' => 2, 'image' => 'SAGaming.jfif', 'rate' => 1.0000],

            ['product_id' => 21, 'game_type_id' => 1, 'image' => 'SpadeGaming.jfif', 'rate' => 1.0000],
            ['product_id' => 22, 'game_type_id' => 8, 'image' => 'SpadeGaming.jfif', 'rate' => 1.0000],
            ['product_id' => 23, 'game_type_id' => 2, 'image' => 'WMCasino.png', 'rate' => 1.0000],
            ['product_id' => 24, 'game_type_id' => 1, 'image' => 'habanero.png', 'rate' => 1.0000],
            ['product_id' => 25, 'game_type_id' => 3, 'image' => 'WBet', 'rate' => 1.0000],
            ['product_id' => 26, 'game_type_id' => 1, 'image' => 'awc.png', 'rate' => 1.0000],
            ['product_id' => 27, 'game_type_id' => 8, 'image' => 'awc.png', 'rate' => 1.0000],
            ['product_id' => 28, 'game_type_id' => 2, 'image' => 'awc.png', 'rate' => 1.0000],
            ['product_id' => 29, 'game_type_id' => 9, 'image' => 'awc.png', 'rate' => 1.0000],
            ['product_id' => 30, 'game_type_id' => 3, 'image' => 'ibc.png', 'rate' => 1.0000],
            ['product_id' => 31, 'game_type_id' => 4, 'image' => 'ibc.png', 'rate' => 1.0000],
            ['product_id' => 32, 'game_type_id' => 13, 'image' => 'ibc.jfif', 'rate' => 1.0000],
            ['product_id' => 33, 'game_type_id' => 2, 'image' => 'DreamGaming.png', 'rate' => 1.0000],
            ['product_id' => 34, 'game_type_id' => 2, 'image' => 'BigGaming.png', 'rate' => 1.0000],
            ['product_id' => 35, 'game_type_id' => 8, 'image' => 'BigGaming.png', 'rate' => 1.0000],
            ['product_id' => 36, 'game_type_id' => 1, 'image' => 'evoplay.png', 'rate' => 1.0000],
            ['product_id' => 37, 'game_type_id' => 1, 'image' => 'AP.png', 'rate' => 1.0000],
            ['product_id' => 38, 'game_type_id' => 2, 'image' => 'CT855', 'rate' => 1.0000],
            ['product_id' => 39, 'game_type_id' => 1, 'image' => 'Mr_Slotty.png', 'rate' => 1.0000],
            ['product_id' => 40, 'game_type_id' => 5, 'image' => 'Mr_Slotty.png', 'rate' => 1.0000],
            ['product_id' => 41, 'game_type_id' => 13, 'image' => 'Mr_Slotty.png', 'rate' => 1.0000],
            ['product_id' => 57, 'game_type_id' => 1, 'image' => 'playace.png', 'rate' => 1.0000],
            ['product_id' => 58, 'game_type_id' => 2, 'image' => 'playacr_casino.png', 'rate' => 1.0000],
            ['product_id' => 59, 'game_type_id' => 1, 'image' => 'booming_game.png', 'rate' => 1.0000],
            ['product_id' => 60, 'game_type_id' => 13, 'image' => 'Spribe.png', 'rate' => 1.0000],
            ['product_id' => 61, 'game_type_id' => 12, 'image' => 'Gaming_World.png', 'rate' => 1.0000],
            ['product_id' => 62, 'game_type_id' => 1, 'image' => 'Gaming_World.png', 'rate' => 1.0000],
            ['product_id' => 63, 'game_type_id' => 7, 'image' => 'Gaming_World.png', 'rate' => 1.0000],
            ['product_id' => 64, 'game_type_id' => 2, 'image' => 'AI Live Casino', 'rate' => 1.0000],
            ['product_id' => 65, 'game_type_id' => 1, 'image' => 'Hacksaw.png', 'rate' => 1.0000],
            ['product_id' => 66, 'game_type_id' => 1, 'image' => 'BIGPOT.png', 'rate' => 1.0000],
            ['product_id' => 67, 'game_type_id' => 13, 'image' => 'IMoon.png', 'rate' => 1.0000],
            ['product_id' => 68, 'game_type_id' => 1, 'image' => 'pascal_gaming.png', 'rate' => 1.0000],
            ['product_id' => 69, 'game_type_id' => 1, 'image' => 'EpicWin.png', 'rate' => 1.0000],
            ['product_id' => 70, 'game_type_id' => 1, 'image' => 'FaChai.png', 'rate' => 1.0000],
            ['product_id' => 71, 'game_type_id' => 8, 'image' => 'FaChai.png', 'rate' => 1.0000],
            ['product_id' => 72, 'game_type_id' => 1, 'image' => 'n2.png', 'rate' => 1.0000],
            ['product_id' => 75, 'game_type_id' => 13, 'image' => 'aviatrix.png', 'rate' => 1.0000],
            ['product_id' => 76, 'game_type_id' => 1, 'image' => 'SmartSoft.png', 'rate' => 1.0000],
            ['product_id' => 77, 'game_type_id' => 1, 'image' => 'World_Entertainment.png', 'rate' => 1.0000],
            ['product_id' => 78, 'game_type_id' => 3, 'image' => 'World_Entertainment.png', 'rate' => 1.0000],
            ['product_id' => 79, 'game_type_id' => 4, 'image' => 'World_Entertainment.png', 'rate' => 1.0000],
            ['product_id' => 80, 'game_type_id' => 2, 'image' => 'World_Entertainment.png', 'rate' => 1.0000],
            ['product_id' => 82, 'game_type_id' => 1, 'image' => 'r88_slot Entertainment.png', 'rate' => 1.0000],
            ['product_id' => 90, 'game_type_id' => 2, 'image' => 'YeeBet.png', 'rate' => 1.0000],
            ['product_id' => 93, 'game_type_id' => 12, 'image' => 'SBO.png', 'rate' => 1.0000],
            ['product_id' => 95, 'game_type_id' => 3, 'image' => 'SBO.png', 'rate' => 1.0000],
        ];

        DB::table('game_type_product')->insert($data);
    }
}
