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
            ['product_id' => 1, 'game_type_id' => 3, 'image' => 'SBO', 'rate' => 1.0000],
            ['product_id' => 2, 'game_type_id' => 2, 'image' => 'YeeBet', 'rate' => 1.0000],
            ['product_id' => 3, 'game_type_id' => 1, 'image' => 'PlayTech', 'rate' => 1.0000],
            ['product_id' => 4, 'game_type_id' => 2, 'image' => 'PlayTech', 'rate' => 1.0000],
            ['product_id' => 5, 'game_type_id' => 1, 'image' => 'Joker', 'rate' => 1.0000],
            ['product_id' => 6, 'game_type_id' => 13, 'image' => 'Joker', 'rate' => 1.0000],
            ['product_id' => 7, 'game_type_id' => 8, 'image' => 'Joker', 'rate' => 1.0000],
            ['product_id' => 8, 'game_type_id' => 2, 'image' => 'SA Gaming', 'rate' => 1.0000],
            ['product_id' => 9, 'game_type_id' => 1, 'image' => 'SpadeGaming', 'rate' => 1.0000],
            ['product_id' => 10, 'game_type_id' => 8, 'image' => 'SpadeGaming', 'rate' => 1.0000],
            ['product_id' => 11, 'game_type_id' => 1, 'image' => 'Live22', 'rate' => 1.0000],
            ['product_id' => 12, 'game_type_id' => 2, 'image' => 'WMCasino', 'rate' => 1.0000],
            ['product_id' => 13, 'game_type_id' => 1, 'image' => 'Habanero', 'rate' => 1.0000],
            ['product_id' => 14, 'game_type_id' => 3, 'image' => 'WBet', 'rate' => 1.0000],
            ['product_id' => 15, 'game_type_id' => 1, 'image' => 'FASTSPIN', 'rate' => 1.0000],
            ['product_id' => 16, 'game_type_id' => 8, 'image' => 'FASTSPIN', 'rate' => 1.0000],
            ['product_id' => 17, 'game_type_id' => 2, 'image' => 'SexyGaming', 'rate' => 1.0000],
            ['product_id' => 18, 'game_type_id' => 9, 'image' => 'SV388', 'rate' => 1.0000],
            ['product_id' => 19, 'game_type_id' => 3, 'image' => 'Saba', 'rate' => 1.0000],
            ['product_id' => 20, 'game_type_id' => 1, 'image' => 'PGSoft', 'rate' => 1.0000],
            ['product_id' => 21, 'game_type_id' => 14, 'image' => 'PragmaticPlay', 'rate' => 1.0000],
            ['product_id' => 22, 'game_type_id' => 4, 'image' => 'PragmaticPlay', 'rate' => 1.0000],
            ['product_id' => 23, 'game_type_id' => 1, 'image' => 'PragmaticPlay', 'rate' => 1.0000],
            ['product_id' => 24, 'game_type_id' => 2, 'image' => 'PragmaticPlay', 'rate' => 1.0000],
            ['product_id' => 25, 'game_type_id' => 2, 'image' => 'Dream Gaming', 'rate' => 1.0000],
            ['product_id' => 26, 'game_type_id' => 2, 'image' => 'BigGaming', 'rate' => 1.0000],
            ['product_id' => 27, 'game_type_id' => 8, 'image' => 'BigGaming', 'rate' => 1.0000],
            ['product_id' => 28, 'game_type_id' => 1, 'image' => 'EvoPlay', 'rate' => 1.0000],
            ['product_id' => 29, 'game_type_id' => 1, 'image' => 'JDB', 'rate' => 1.0000],
            ['product_id' => 30, 'game_type_id' => 8, 'image' => 'JDB', 'rate' => 1.0000],
            ['product_id' => 31, 'game_type_id' => 13, 'image' => 'JDB', 'rate' => 1.0000],
            ['product_id' => 32, 'game_type_id' => 1, 'image' => 'PlayStar', 'rate' => 1.0000],
            ['product_id' => 33, 'game_type_id' => 2, 'image' => 'CT855', 'rate' => 1.0000],
            ['product_id' => 34, 'game_type_id' => 1, 'image' => 'CQ9', 'rate' => 1.0000],
            ['product_id' => 35, 'game_type_id' => 8, 'image' => 'CQ9', 'rate' => 1.0000],
            ['product_id' => 36, 'game_type_id' => 1, 'image' => 'JiLi', 'rate' => 1.0000],
            ['product_id' => 37, 'game_type_id' => 8, 'image' => 'JiLi', 'rate' => 1.0000],
            ['product_id' => 38, 'game_type_id' => 2, 'image' => 'JiLi', 'rate' => 1.0000],
            ['product_id' => 39, 'game_type_id' => 12, 'image' => 'JiLi', 'rate' => 1.0000],
            ['product_id' => 40, 'game_type_id' => 1, 'image' => 'BGaming', 'rate' => 1.0000],
            ['product_id' => 41, 'game_type_id' => 1, 'image' => 'Booongo', 'rate' => 1.0000],
            ['product_id' => 42, 'game_type_id' => 1, 'image' => 'Fazi', 'rate' => 1.0000],
            ['product_id' => 43, 'game_type_id' => 1, 'image' => 'Felix', 'rate' => 1.0000],
            ['product_id' => 44, 'game_type_id' => 1, 'image' => 'Funta', 'rate' => 1.0000],
            ['product_id' => 45, 'game_type_id' => 8, 'image' => 'Funta', 'rate' => 1.0000],
            ['product_id' => 46, 'game_type_id' => 1, 'image' => 'GamingWorld', 'rate' => 1.0000],
            ['product_id' => 47, 'game_type_id' => 1, 'image' => 'KAGaming', 'rate' => 1.0000],
            ['product_id' => 48, 'game_type_id' => 1, 'image' => 'Kiron', 'rate' => 1.0000],
            ['product_id' => 49, 'game_type_id' => 1, 'image' => 'MrSlotty', 'rate' => 1.0000],
            ['product_id' => 50, 'game_type_id' => 1, 'image' => 'NetGame', 'rate' => 1.0000],
            ['product_id' => 51, 'game_type_id' => 8, 'image' => 'NetGame', 'rate' => 1.0000],
            ['product_id' => 52, 'game_type_id' => 1, 'image' => 'RedRake', 'rate' => 1.0000],
            ['product_id' => 53, 'game_type_id' => 1, 'image' => 'Volt Entertainment', 'rate' => 1.0000],
            ['product_id' => 54, 'game_type_id' => 1, 'image' => 'ZeusPlay', 'rate' => 1.0000],
            ['product_id' => 55, 'game_type_id' => 1, 'image' => 'PlayAce', 'rate' => 1.0000],
            ['product_id' => 56, 'game_type_id' => 2, 'image' => 'PlayAce', 'rate' => 1.0000],
            ['product_id' => 57, 'game_type_id' => 1, 'image' => 'Booming Games', 'rate' => 1.0000],
            ['product_id' => 58, 'game_type_id' => 13, 'image' => 'Spribe', 'rate' => 1.0000],
            ['product_id' => 59, 'game_type_id' => 12, 'image' => 'WOW Gaming', 'rate' => 1.0000],
            ['product_id' => 60, 'game_type_id' => 1, 'image' => 'WOW Gaming', 'rate' => 1.0000],
            ['product_id' => 61, 'game_type_id' => 7, 'image' => 'WOW Gaming', 'rate' => 1.0000],
            ['product_id' => 62, 'game_type_id' => 2, 'image' => 'AI Live Casino', 'rate' => 1.0000],
            ['product_id' => 63, 'game_type_id' => 12, 'image' => 'AI Live Casino', 'rate' => 1.0000],
            ['product_id' => 64, 'game_type_id' => 1, 'image' => 'Hacksaw', 'rate' => 1.0000],
            ['product_id' => 65, 'game_type_id' => 13, 'image' => 'Hacksaw', 'rate' => 1.0000],
            ['product_id' => 66, 'game_type_id' => 1, 'image' => 'BIGPOT', 'rate' => 1.0000],
            ['product_id' => 67, 'game_type_id' => 13, 'image' => 'IMoon', 'rate' => 1.0000],
            ['product_id' => 68, 'game_type_id' => 1, 'image' => 'EpicWin', 'rate' => 1.0000],
            ['product_id' => 69, 'game_type_id' => 1, 'image' => 'FaChai', 'rate' => 1.0000],
            ['product_id' => 70, 'game_type_id' => 8, 'image' => 'FaChai', 'rate' => 1.0000],
            ['product_id' => 71, 'game_type_id' => 1, 'image' => 'NOVOMatic', 'rate' => 1.0000],
            ['product_id' => 72, 'game_type_id' => 1, 'image' => 'OCTOPlay', 'rate' => 1.0000],
            ['product_id' => 73, 'game_type_id' => 3, 'image' => 'Digitain', 'rate' => 1.0000],
            ['product_id' => 74, 'game_type_id' => 13, 'image' => 'Aviatrix', 'rate' => 1.0000],
            ['product_id' => 75, 'game_type_id' => 1, 'image' => 'SmartSoft', 'rate' => 1.0000],
            ['product_id' => 76, 'game_type_id' => 1, 'image' => 'World Entertainment', 'rate' => 1.0000],
            ['product_id' => 77, 'game_type_id' => 3, 'image' => 'World Entertainment', 'rate' => 1.0000],
            ['product_id' => 78, 'game_type_id' => 4, 'image' => 'World Entertainment', 'rate' => 1.0000],
            ['product_id' => 79, 'game_type_id' => 2, 'image' => 'World Entertainment', 'rate' => 1.0000],
            ['product_id' => 80, 'game_type_id' => 3, 'image' => 'FB Sports', 'rate' => 1.0000],
            ['product_id' => 81, 'game_type_id' => 1, 'image' => 'RiCH88', 'rate' => 1.0000],
            ['product_id' => 82, 'game_type_id' => 2, 'image' => 'King855', 'rate' => 1.0000],
            ['product_id' => 83, 'game_type_id' => 1, 'image' => 'AmigoGaming', 'rate' => 1.0000],
            ['product_id' => 84, 'game_type_id' => 2, 'image' => 'FBGames', 'rate' => 1.0000],
            ['product_id' => 85, 'game_type_id' => 2, 'image' => 'Astar', 'rate' => 1.0000],
            ['product_id' => 86, 'game_type_id' => 13, 'image' => 'Astar', 'rate' => 1.0000],
            ['product_id' => 87, 'game_type_id' => 12, 'image' => 'Astar', 'rate' => 1.0000],
        ];

        DB::table('game_type_product')->insert($data);
    }
}