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
            ['product_id' => 1, 'game_type_id' => 3, 'image' => 'SBO.png', 'rate' => 1.0000],
            ['product_id' => 2, 'game_type_id' => 2, 'image' => 'YeeBet.png', 'rate' => 1.0000],
            ['product_id' => 3, 'game_type_id' => 1, 'image' => 'PlayTech.png', 'rate' => 1.0000],
            ['product_id' => 4, 'game_type_id' => 2, 'image' => 'PlayTech.png', 'rate' => 1.0000],
            ['product_id' => 5, 'game_type_id' => 1, 'image' => 'Joker.png', 'rate' => 1.0000],
            ['product_id' => 6, 'game_type_id' => 13, 'image' => 'Joker.png', 'rate' => 1.0000],
            ['product_id' => 7, 'game_type_id' => 8, 'image' => 'Joker.png', 'rate' => 1.0000],
            ['product_id' => 8, 'game_type_id' => 2, 'image' => 'SAGaming.jfif', 'rate' => 1.0000],
            ['product_id' => 9, 'game_type_id' => 1, 'image' => 'SpadeGaming.jfif', 'rate' => 1.0000],
            ['product_id' => 10, 'game_type_id' => 8, 'image' => 'SpadeGaming.jfif', 'rate' => 1.0000],
            ['product_id' => 11, 'game_type_id' => 1, 'image' => 'Live22.png', 'rate' => 1.0000],
            ['product_id' => 12, 'game_type_id' => 2, 'image' => 'WMCasino.png', 'rate' => 1.0000],
            ['product_id' => 13, 'game_type_id' => 1, 'image' => 'Habanero.png', 'rate' => 1.0000],
            ['product_id' => 14, 'game_type_id' => 3, 'image' => 'WBet', 'rate' => 1.0000],
            ['product_id' => 15, 'game_type_id' => 1, 'image' => 'FASTSPIN.jfif', 'rate' => 1.0000],
            ['product_id' => 16, 'game_type_id' => 8, 'image' => 'FASTSPIN.jfif', 'rate' => 1.0000],
            ['product_id' => 17, 'game_type_id' => 2, 'image' => 'SexyGaming.png', 'rate' => 1.0000],
            ['product_id' => 18, 'game_type_id' => 9, 'image' => 'SV388.jfif', 'rate' => 1.0000],
            ['product_id' => 19, 'game_type_id' => 3, 'image' => 'Saba.png', 'rate' => 1.0000],
            ['product_id' => 20, 'game_type_id' => 1, 'image' => 'PGSoft.png', 'rate' => 1.0000],
            ['product_id' => 21, 'game_type_id' => 14, 'image' => 'PragmaticPlayPremiun', 'rate' => 1.0000],
            ['product_id' => 22, 'game_type_id' => 4, 'image' => 'PragmaticPlayVirtual', 'rate' => 1.0000],
            ['product_id' => 23, 'game_type_id' => 1, 'image' => 'PragmaticPlay', 'rate' => 1.0000],
            ['product_id' => 24, 'game_type_id' => 2, 'image' => 'pragmatic_casino.png', 'rate' => 1.0000],
            ['product_id' => 25, 'game_type_id' => 2, 'image' => 'DreamGaming.png', 'rate' => 1.0000],
            ['product_id' => 26, 'game_type_id' => 2, 'image' => 'BigGaming.png', 'rate' => 1.0000],
            ['product_id' => 27, 'game_type_id' => 8, 'image' => 'BigGaming.png', 'rate' => 1.0000],
            ['product_id' => 28, 'game_type_id' => 1, 'image' => 'EvoPlay.png', 'rate' => 1.0000],
            ['product_id' => 29, 'game_type_id' => 1, 'image' => 'JDB.png', 'rate' => 1.0000],
            ['product_id' => 30, 'game_type_id' => 8, 'image' => 'JDB.png', 'rate' => 1.0000],
            ['product_id' => 31, 'game_type_id' => 13, 'image' => 'JDB.png', 'rate' => 1.0000],
            ['product_id' => 32, 'game_type_id' => 1, 'image' => 'PlayStar.png', 'rate' => 1.0000],
            ['product_id' => 33, 'game_type_id' => 2, 'image' => 'CT855', 'rate' => 1.0000],
            ['product_id' => 34, 'game_type_id' => 1, 'image' => 'cq9.png', 'rate' => 1.0000],
            ['product_id' => 35, 'game_type_id' => 8, 'image' => 'cq9.png', 'rate' => 1.0000],
            ['product_id' => 36, 'game_type_id' => 1, 'image' => 'jili.png', 'rate' => 1.0000],
            ['product_id' => 37, 'game_type_id' => 8, 'image' => 'jili.png', 'rate' => 1.0000],
            ['product_id' => 38, 'game_type_id' => 2, 'image' => 'jili.png', 'rate' => 1.0000],
            ['product_id' => 39, 'game_type_id' => 12, 'image' => 'jili.png', 'rate' => 1.0000],
            ['product_id' => 40, 'game_type_id' => 1, 'image' => 'BGaming.png', 'rate' => 1.0000],
            ['product_id' => 41, 'game_type_id' => 1, 'image' => 'Booongo.png', 'rate' => 1.0000],
            ['product_id' => 42, 'game_type_id' => 1, 'image' => 'Fazi.png', 'rate' => 1.0000],
            ['product_id' => 43, 'game_type_id' => 1, 'image' => 'Felix.png', 'rate' => 1.0000],
            ['product_id' => 44, 'game_type_id' => 1, 'image' => 'Funta.png', 'rate' => 1.0000],
            ['product_id' => 45, 'game_type_id' => 8, 'image' => 'Funta.png', 'rate' => 1.0000],
            ['product_id' => 46, 'game_type_id' => 1, 'image' => 'GamingWorld.png', 'rate' => 1.0000],
            ['product_id' => 47, 'game_type_id' => 1, 'image' => 'KAGaming.png', 'rate' => 1.0000],
            ['product_id' => 48, 'game_type_id' => 1, 'image' => 'KIRON.png', 'rate' => 1.0000],
            ['product_id' => 49, 'game_type_id' => 1, 'image' => 'MrSlotty.png', 'rate' => 1.0000],
            ['product_id' => 50, 'game_type_id' => 1, 'image' => 'NetGame.png', 'rate' => 1.0000],
            ['product_id' => 51, 'game_type_id' => 8, 'image' => 'NetGame.png', 'rate' => 1.0000],
            ['product_id' => 52, 'game_type_id' => 1, 'image' => 'RedRake.png', 'rate' => 1.0000],
            ['product_id' => 53, 'game_type_id' => 1, 'image' => 'Volt Entertainment.png', 'rate' => 1.0000],
            ['product_id' => 54, 'game_type_id' => 1, 'image' => 'ZeusPlay.png', 'rate' => 1.0000],
            ['product_id' => 55, 'game_type_id' => 1, 'image' => 'PlayAce.png', 'rate' => 1.0000],
            ['product_id' => 56, 'game_type_id' => 2, 'image' => 'PlayAce', 'rate' => 1.0000],
            ['product_id' => 57, 'game_type_id' => 1, 'image' => 'Booming Games', 'rate' => 1.0000],
            ['product_id' => 58, 'game_type_id' => 13, 'image' => 'Spribe', 'rate' => 1.0000],
            ['product_id' => 59, 'game_type_id' => 12, 'image' => 'WOW Gaming', 'rate' => 1.0000],
            ['product_id' => 60, 'game_type_id' => 1, 'image' => 'WOW Gaming', 'rate' => 1.0000],
            ['product_id' => 61, 'game_type_id' => 7, 'image' => 'WOW Gaming', 'rate' => 1.0000],
            ['product_id' => 62, 'game_type_id' => 2, 'image' => 'AI Live Casino', 'rate' => 1.0000],
            ['product_id' => 63, 'game_type_id' => 12, 'image' => 'AI Live Casino', 'rate' => 1.0000],
            ['product_id' => 64, 'game_type_id' => 1, 'image' => 'Hacksaw.png', 'rate' => 1.0000],
            ['product_id' => 65, 'game_type_id' => 13, 'image' => 'Hacksaw.png', 'rate' => 1.0000],
            ['product_id' => 66, 'game_type_id' => 1, 'image' => 'BIGPOT.png', 'rate' => 1.0000],
            ['product_id' => 67, 'game_type_id' => 13, 'image' => 'IMoon.png', 'rate' => 1.0000],
            ['product_id' => 68, 'game_type_id' => 1, 'image' => 'EpicWin.png', 'rate' => 1.0000],
            ['product_id' => 69, 'game_type_id' => 1, 'image' => 'FaChai.png', 'rate' => 1.0000],
            ['product_id' => 70, 'game_type_id' => 8, 'image' => 'FaChai.png', 'rate' => 1.0000],
            ['product_id' => 71, 'game_type_id' => 1, 'image' => 'NOVOMatic.png', 'rate' => 1.0000],
            ['product_id' => 72, 'game_type_id' => 1, 'image' => 'OCTOPlay.png', 'rate' => 1.0000],
            ['product_id' => 73, 'game_type_id' => 3, 'image' => 'Digitain.png', 'rate' => 1.0000],
            ['product_id' => 74, 'game_type_id' => 13, 'image' => 'Aviatrix.png', 'rate' => 1.0000],
            ['product_id' => 75, 'game_type_id' => 1, 'image' => 'SmartSoft.png', 'rate' => 1.0000],
            ['product_id' => 76, 'game_type_id' => 1, 'image' => 'World Entertainment.png', 'rate' => 1.0000],
            ['product_id' => 77, 'game_type_id' => 3, 'image' => 'World Entertainment.png', 'rate' => 1.0000],
            ['product_id' => 78, 'game_type_id' => 4, 'image' => 'World Entertainment.png', 'rate' => 1.0000],
            ['product_id' => 79, 'game_type_id' => 2, 'image' => 'World Entertainment.png', 'rate' => 1.0000],
            ['product_id' => 80, 'game_type_id' => 3, 'image' => 'FBSports.png', 'rate' => 1.0000],
            ['product_id' => 81, 'game_type_id' => 1, 'image' => 'RiCH88.png', 'rate' => 1.0000],
            ['product_id' => 82, 'game_type_id' => 2, 'image' => 'King855.png', 'rate' => 1.0000],
            ['product_id' => 83, 'game_type_id' => 1, 'image' => 'AmigoGaming.png', 'rate' => 1.0000],
            ['product_id' => 84, 'game_type_id' => 2, 'image' => 'FBGames.png', 'rate' => 1.0000],
            ['product_id' => 85, 'game_type_id' => 2, 'image' => 'Astar.png', 'rate' => 1.0000],
            ['product_id' => 86, 'game_type_id' => 13, 'image' => 'Astar.png', 'rate' => 1.0000],
            ['product_id' => 87, 'game_type_id' => 12, 'image' => 'Astar.png', 'rate' => 1.0000],
        ];

        DB::table('game_type_product')->insert($data);
    }
}
