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
            // SPRIBE (product_id 1) => OTHERS (game_type_id 13)
            ['product_id' => 1, 'game_type_id' => 13, 'image' => 'SPRIBE', 'rate' => 1.0000],
            // WM Casino (product_id 2) => LIVE_CASINO (game_type_id 2)
            ['product_id' => 2, 'game_type_id' => 2, 'image' => 'WM Casino', 'rate' => 1.0000],
            // King855/CT855 (K9) (product_id 3) => LIVE_CASINO (game_type_id 2)
            ['product_id' => 3, 'game_type_id' => 2, 'image' => 'King855/CT855 (K9)', 'rate' => 1.0000],
            // King855/CT855(K0) (product_id 4) => LIVE_CASINO (game_type_id 2)
            ['product_id' => 4, 'game_type_id' => 2, 'image' => 'King855/CT855(K0)', 'rate' => 1.0000],
            // BGaming (product_id 5) => SLOT (game_type_id 1)
            ['product_id' => 5, 'game_type_id' => 1, 'image' => 'BGaming', 'rate' => 1.0000],
            // KA Gaming (product_id 6) => SLOT (game_type_id 1)
            ['product_id' => 6, 'game_type_id' => 1, 'image' => 'KA Gaming', 'rate' => 1.0000],
            // Booongo (product_id 7) => SLOT (game_type_id 1)
            ['product_id' => 7, 'game_type_id' => 1, 'image' => 'Booongo', 'rate' => 1.0000],
            // Funta Gaming (product_id 8) => SLOT (game_type_id 1), FISHING (game_type_id 8)
            ['product_id' => 8, 'game_type_id' => 1, 'image' => 'Funta Gaming', 'rate' => 1.0000],
            ['product_id' => 8, 'game_type_id' => 8, 'image' => 'Funta Gaming', 'rate' => 1.0000],
            // Gaming World (product_id 9) => SLOT (game_type_id 1)
            ['product_id' => 9, 'game_type_id' => 1, 'image' => 'Gaming World', 'rate' => 1.0000],
            // Felix Gaming (product_id 10) => SLOT (game_type_id 1)
            ['product_id' => 10, 'game_type_id' => 1, 'image' => 'Felix Gaming', 'rate' => 1.0000],
            // KIRON (product_id 11) => SLOT (game_type_id 1)
            ['product_id' => 11, 'game_type_id' => 1, 'image' => 'KIRON', 'rate' => 1.0000],
            // Mr Slotty (product_id 12) => SLOT (game_type_id 1)
            ['product_id' => 12, 'game_type_id' => 1, 'image' => 'Mr Slotty', 'rate' => 1.0000],
            // Net Game (product_id 13) => SLOT (game_type_id 1), FISHING (game_type_id 8)
            ['product_id' => 13, 'game_type_id' => 1, 'image' => 'Net Game', 'rate' => 1.0000],
            ['product_id' => 13, 'game_type_id' => 8, 'image' => 'Net Game', 'rate' => 1.0000],
            // Red Rake (product_id 14) => SLOT (game_type_id 1)
            ['product_id' => 14, 'game_type_id' => 1, 'image' => 'Red Rake', 'rate' => 1.0000],
            // Fazi (product_id 15) => SLOT (game_type_id 1)
            ['product_id' => 15, 'game_type_id' => 1, 'image' => 'Fazi', 'rate' => 1.0000],
            // ZeusPlay (product_id 16) => SLOT (game_type_id 1)
            ['product_id' => 16, 'game_type_id' => 1, 'image' => 'ZeusPlay', 'rate' => 1.0000],
            // Volt Entertainment (product_id 17) => SLOT (game_type_id 1)
            ['product_id' => 17, 'game_type_id' => 1, 'image' => 'Volt Entertainment', 'rate' => 1.0000],
            // WOW Gaming (product_id 18) => POKER (game_type_id 12), SLOT (game_type_id 1), P2P (game_type_id 7)
            ['product_id' => 18, 'game_type_id' => 12, 'image' => 'WOW Gaming', 'rate' => 1.0000],
            ['product_id' => 18, 'game_type_id' => 1, 'image' => 'WOW Gaming', 'rate' => 1.0000],
            ['product_id' => 18, 'game_type_id' => 7, 'image' => 'WOW Gaming', 'rate' => 1.0000],
            // AI LIVE CASINO (product_id 19) => LIVE_CASINO (game_type_id 2), POKER (game_type_id 12)
            ['product_id' => 19, 'game_type_id' => 2, 'image' => 'AI LIVE CASINO', 'rate' => 1.0000],
            ['product_id' => 19, 'game_type_id' => 12, 'image' => 'AI LIVE CASINO', 'rate' => 1.0000],
            // Sexy Gaming (product_id 20) => LIVE_CASINO (game_type_id 2)
            ['product_id' => 20, 'game_type_id' => 2, 'image' => 'Sexy Gaming', 'rate' => 1.0000],
            // SV388 (product_id 21) => COCK_FIGHTING (game_type_id 9)
            ['product_id' => 21, 'game_type_id' => 9, 'image' => 'SV388', 'rate' => 1.0000],
            // FASTSPIN (product_id 22) => SLOT (game_type_id 1), FISHING (game_type_id 8)
            ['product_id' => 22, 'game_type_id' => 1, 'image' => 'FASTSPIN', 'rate' => 1.0000],
            ['product_id' => 22, 'game_type_id' => 8, 'image' => 'FASTSPIN', 'rate' => 1.0000],
            // PlayStar (product_id 23) => SLOT (game_type_id 1)
            ['product_id' => 23, 'game_type_id' => 1, 'image' => 'PlayStar', 'rate' => 1.0000],
            // CQ9 (product_id 24) => SLOT (game_type_id 1), FISHING (game_type_id 8)
            ['product_id' => 24, 'game_type_id' => 1, 'image' => 'CQ9', 'rate' => 1.0000],
            ['product_id' => 24, 'game_type_id' => 8, 'image' => 'CQ9', 'rate' => 1.0000],
            // Play Tech (product_id 25) => LIVE_CASINO (game_type_id 2), SLOT (game_type_id 1)
            ['product_id' => 25, 'game_type_id' => 2, 'image' => 'Play Tech', 'rate' => 1.0000],
            ['product_id' => 25, 'game_type_id' => 1, 'image' => 'Play Tech', 'rate' => 1.0000],
            // YEE Bet (product_id 26) => LIVE_CASINO (game_type_id 2)
            ['product_id' => 26, 'game_type_id' => 2, 'image' => 'YEE Bet', 'rate' => 1.0000],
            // PG Soft (Direct) (product_id 27) => SLOT (game_type_id 1)
            ['product_id' => 27, 'game_type_id' => 1, 'image' => 'PG Soft (Direct)', 'rate' => 1.0000],
            // IBC-SABA (product_id 28) => SPORT_BOOK (game_type_id 3), OTHERS (game_type_id 13), VIRTUAL_SPORT (game_type_id 4)
            ['product_id' => 28, 'game_type_id' => 3, 'image' => 'IBC-SABA', 'rate' => 1.0000],
            ['product_id' => 28, 'game_type_id' => 13, 'image' => 'IBC-SABA', 'rate' => 1.0000],
            ['product_id' => 28, 'game_type_id' => 4, 'image' => 'IBC-SABA', 'rate' => 1.0000],
            // Pragmatic Play (product_id 29) => LIVE_CASINO (game_type_id 2), SLOT (game_type_id 1), VIRTUAL_SPORT (game_type_id 4)
            ['product_id' => 29, 'game_type_id' => 2, 'image' => 'Pragmatic Play', 'rate' => 1.0000],
            ['product_id' => 29, 'game_type_id' => 1, 'image' => 'Pragmatic Play', 'rate' => 1.0000],
            ['product_id' => 29, 'game_type_id' => 4, 'image' => 'Pragmatic Play', 'rate' => 1.0000],
            // jili (product_id 30) => SLOT (game_type_id 1), FISHING (game_type_id 8), LIVE_CASINO (game_type_id 2), POKER (game_type_id 12)
            ['product_id' => 30, 'game_type_id' => 1, 'image' => 'jili', 'rate' => 1.0000],
            ['product_id' => 30, 'game_type_id' => 8, 'image' => 'jili', 'rate' => 1.0000],
            ['product_id' => 30, 'game_type_id' => 2, 'image' => 'jili', 'rate' => 1.0000],
            ['product_id' => 30, 'game_type_id' => 12, 'image' => 'jili', 'rate' => 1.0000],
            // Tada (product_id 31) => SLOT (game_type_id 1), FISHING (game_type_id 8), LIVE_CASINO (game_type_id 2), POKER (game_type_id 12)
            ['product_id' => 31, 'game_type_id' => 1, 'image' => 'Tada', 'rate' => 1.0000],
            ['product_id' => 31, 'game_type_id' => 8, 'image' => 'Tada', 'rate' => 1.0000],
            ['product_id' => 31, 'game_type_id' => 2, 'image' => 'Tada', 'rate' => 1.0000],
            ['product_id' => 31, 'game_type_id' => 12, 'image' => 'Tada', 'rate' => 1.0000],
            // SBO (product_id 32) => SPORT_BOOK (game_type_id 3), LIVE_CASINO (game_type_id 2), POKER (game_type_id 12), VIRTUAL_SPORT (game_type_id 4)
            ['product_id' => 32, 'game_type_id' => 3, 'image' => 'SBO', 'rate' => 1.0000],
            ['product_id' => 32, 'game_type_id' => 2, 'image' => 'SBO', 'rate' => 1.0000],
            ['product_id' => 32, 'game_type_id' => 12, 'image' => 'SBO', 'rate' => 1.0000],
            ['product_id' => 32, 'game_type_id' => 4, 'image' => 'SBO', 'rate' => 1.0000],
            // Dream Gaming (product_id 33) => LIVE_CASINO (game_type_id 2)
            ['product_id' => 33, 'game_type_id' => 2, 'image' => 'Dream Gaming', 'rate' => 1.0000],
            // JDB (product_id 34) => SLOT (game_type_id 1), FISHING (game_type_id 8), OTHERS (game_type_id 13)
            ['product_id' => 34, 'game_type_id' => 1, 'image' => 'JDB', 'rate' => 1.0000],
            ['product_id' => 34, 'game_type_id' => 8, 'image' => 'JDB', 'rate' => 1.0000],
            ['product_id' => 34, 'game_type_id' => 13, 'image' => 'JDB', 'rate' => 1.0000],
            // Evoplay (product_id 35) => SLOT (game_type_id 1)
            ['product_id' => 35, 'game_type_id' => 1, 'image' => 'Evoplay', 'rate' => 1.0000],
            // WBET (product_id 36) => SPORT_BOOK (game_type_id 3)
            ['product_id' => 36, 'game_type_id' => 3, 'image' => 'WBET', 'rate' => 1.0000],
            // Hacksaw (product_id 37) => SLOT (game_type_id 1)
            ['product_id' => 37, 'game_type_id' => 1, 'image' => 'Hacksaw', 'rate' => 1.0000],
            // Bigpot (product_id 38) => SLOT (game_type_id 1)
            ['product_id' => 38, 'game_type_id' => 1, 'image' => 'Bigpot', 'rate' => 1.0000],
            // Live22 (product_id 39) => SLOT (game_type_id 1)
            ['product_id' => 39, 'game_type_id' => 1, 'image' => 'Live22', 'rate' => 1.0000],
            // IMOON (product_id 40) => OTHERS (game_type_id 13)
            ['product_id' => 40, 'game_type_id' => 13, 'image' => 'IMOON', 'rate' => 1.0000],
            // Big Gaming (product_id 41) => LIVE_CASINO (game_type_id 2), FISHING (game_type_id 8)
            ['product_id' => 41, 'game_type_id' => 2, 'image' => 'Big Gaming', 'rate' => 1.0000],
            ['product_id' => 41, 'game_type_id' => 8, 'image' => 'Big Gaming', 'rate' => 1.0000],
            // EPICWIN (product_id 42) => SLOT (game_type_id 1)
            ['product_id' => 42, 'game_type_id' => 1, 'image' => 'EPICWIN', 'rate' => 1.0000],
            // NOVOMATIC (product_id 43) => SLOT (game_type_id 1)
            ['product_id' => 43, 'game_type_id' => 1, 'image' => 'NOVOMATIC', 'rate' => 1.0000],
            // Octoplay (product_id 44) => SLOT (game_type_id 1)
            ['product_id' => 44, 'game_type_id' => 1, 'image' => 'Octoplay', 'rate' => 1.0000],
            // aviatrix (product_id 45) => OTHERS (game_type_id 13)
            ['product_id' => 45, 'game_type_id' => 13, 'image' => 'aviatrix', 'rate' => 1.0000],
            // DIGITAIN (product_id 46) => SPORT_BOOK (game_type_id 3)
            ['product_id' => 46, 'game_type_id' => 3, 'image' => 'DIGITAIN', 'rate' => 1.0000],
            // Fachai (product_id 47) => SLOT (game_type_id 1), FISHING (game_type_id 8)
            ['product_id' => 47, 'game_type_id' => 1, 'image' => 'Fachai', 'rate' => 1.0000],
            ['product_id' => 47, 'game_type_id' => 8, 'image' => 'Fachai', 'rate' => 1.0000],
            // smartsoft (product_id 48) => SLOT (game_type_id 1)
            ['product_id' => 48, 'game_type_id' => 1, 'image' => 'smartsoft', 'rate' => 1.0000],
            // World Entertainment (product_id 49) => LIVE_CASINO (game_type_id 2), SPORT_BOOK (game_type_id 3), SLOT (game_type_id 1), VIRTUAL_SPORT (game_type_id 4)
            ['product_id' => 49, 'game_type_id' => 2, 'image' => 'World Entertainment', 'rate' => 1.0000],
            ['product_id' => 49, 'game_type_id' => 3, 'image' => 'World Entertainment', 'rate' => 1.0000],
            ['product_id' => 49, 'game_type_id' => 1, 'image' => 'World Entertainment', 'rate' => 1.0000],
            ['product_id' => 49, 'game_type_id' => 4, 'image' => 'World Entertainment', 'rate' => 1.0000],
            // FB SPORT (product_id 50) => SPORT_BOOK (game_type_id 3)
            ['product_id' => 50, 'game_type_id' => 3, 'image' => 'FB SPORT', 'rate' => 1.0000],
            // Evolution（ASIA） (product_id 51) => LIVE_CASINO (game_type_id 2), SLOT (game_type_id 1)
            ['product_id' => 51, 'game_type_id' => 2, 'image' => 'Evolution（ASIA）', 'rate' => 1.0000],
            ['product_id' => 51, 'game_type_id' => 1, 'image' => 'Evolution（ASIA）', 'rate' => 1.0000],
            // Netent（ASIA） (product_id 52) => SLOT (game_type_id 1)
            ['product_id' => 52, 'game_type_id' => 1, 'image' => 'Netent（ASIA）', 'rate' => 1.0000],
            // Red Tiger（ASIA） (product_id 53) => SLOT (game_type_id 1)
            ['product_id' => 53, 'game_type_id' => 1, 'image' => 'Red Tiger（ASIA）', 'rate' => 1.0000],
            // no limit city （ASIA） (product_id 54) => SLOT (game_type_id 1)
            ['product_id' => 54, 'game_type_id' => 1, 'image' => 'no limit city （ASIA）', 'rate' => 1.0000],
            // big time gaming （ASIA） (product_id 55) => SLOT (game_type_id 1)
            ['product_id' => 55, 'game_type_id' => 1, 'image' => 'big time gaming （ASIA）', 'rate' => 1.0000],
            // Evolution (LATAM) (product_id 56) => LIVE_CASINO (game_type_id 2), OTHERS (game_type_id 13)
            ['product_id' => 56, 'game_type_id' => 2, 'image' => 'Evolution (LATAM)', 'rate' => 1.0000],
            ['product_id' => 56, 'game_type_id' => 13, 'image' => 'Evolution (LATAM)', 'rate' => 1.0000],
            // Netent (LATAM) (product_id 57) => SLOT (game_type_id 1)
            ['product_id' => 57, 'game_type_id' => 1, 'image' => 'Netent (LATAM)', 'rate' => 1.0000],
            // Red Tiger (LATAM) (product_id 58) => SLOT (game_type_id 1)
            ['product_id' => 58, 'game_type_id' => 1, 'image' => 'Red Tiger (LATAM)', 'rate' => 1.0000],
            // no limit city (LATAM) (product_id 59) => SLOT (game_type_id 1)
            ['product_id' => 59, 'game_type_id' => 1, 'image' => 'no limit city (LATAM)', 'rate' => 1.0000],
            // big time gaming(LATAM) (product_id 60) => SLOT (game_type_id 1)
            ['product_id' => 60, 'game_type_id' => 1, 'image' => 'big time gaming(LATAM)', 'rate' => 1.0000],
            // Rich88 (product_id 61) => SLOT (game_type_id 1)
            ['product_id' => 61, 'game_type_id' => 1, 'image' => 'Rich88', 'rate' => 1.0000],
            // SA Gaming (product_id 62) => LIVE_CASINO (game_type_id 2)
            ['product_id' => 62, 'game_type_id' => 2, 'image' => 'SA Gaming', 'rate' => 1.0000],
            // BOOMING GAMES (product_id 63) => SLOT (game_type_id 1)
            ['product_id' => 63, 'game_type_id' => 1, 'image' => 'BOOMING GAMES', 'rate' => 1.0000],
            // AMIGO GAMING (product_id 64) => SLOT (game_type_id 1)
            ['product_id' => 64, 'game_type_id' => 1, 'image' => 'AMIGO GAMING', 'rate' => 1.0000],
            // FB Games (product_id 65) => LIVE_CASINO (game_type_id 2)
            ['product_id' => 65, 'game_type_id' => 2, 'image' => 'FB Games', 'rate' => 1.0000],
            // Habanero (product_id 66) => SLOT (game_type_id 1)
            ['product_id' => 66, 'game_type_id' => 1, 'image' => 'Habanero', 'rate' => 1.0000],
            // PlayAce (product_id 67) => SLOT (game_type_id 1), LIVE_CASINO (game_type_id 2)
            ['product_id' => 67, 'game_type_id' => 1, 'image' => 'PlayAce', 'rate' => 1.0000],
            ['product_id' => 67, 'game_type_id' => 2, 'image' => 'PlayAce', 'rate' => 1.0000],
            // SPADE GAMING (product_id 68) => SLOT (game_type_id 1), FISHING (game_type_id 8)
            ['product_id' => 68, 'game_type_id' => 1, 'image' => 'SPADE GAMING', 'rate' => 1.0000],
            ['product_id' => 68, 'game_type_id' => 8, 'image' => 'SPADE GAMING', 'rate' => 1.0000],
            // ADVANTPLAY (product_id 69) => SLOT (game_type_id 1)
            ['product_id' => 69, 'game_type_id' => 1, 'image' => 'ADVANTPLAY', 'rate' => 1.0000],
            // TF Gaming (product_id 70) => ESPORT (game_type_id 11)
            ['product_id' => 70, 'game_type_id' => 11, 'image' => 'TF Gaming', 'rate' => 1.0000],
            // ASTAR (product_id 71) => LIVE_CASINO (game_type_id 2), OTHERS (game_type_id 13), POKER (game_type_id 12)
            ['product_id' => 71, 'game_type_id' => 2, 'image' => 'ASTAR', 'rate' => 1.0000],
            ['product_id' => 71, 'game_type_id' => 13, 'image' => 'ASTAR', 'rate' => 1.0000],
            ['product_id' => 71, 'game_type_id' => 12, 'image' => 'ASTAR', 'rate' => 1.0000],
        ];

        DB::table('game_type_products')->insert($data);
    }
}
