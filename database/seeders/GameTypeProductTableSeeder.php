<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
class GameTypeProductTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        // Define the mapping between product code and game types.
        // Product codes are integers, so ensure they are treated as such.
        $productGameTypes = [
            1138 => ['OTHERS'], // SPRIBE
            1020 => ['LIVE_CASINO'], // WM Casino
            1038 => ['LIVE_CASINO'], // King855/CT855 (K9)
            1191 => ['LIVE_CASINO'], // King855/CT855(K0)
            1058 => ['SLOT'], // BGaming
            1102 => ['SLOT'], // KA Gaming
            1070 => ['SLOT'], // Booongo
            1097 => ['SLOT', 'FISHING'], // Funta Gaming
            1111 => ['SLOT'], // Gaming World
            1098 => ['SLOT'], // Felix Gaming
            1065 => ['SLOT'], // KIRON
            1055 => ['SLOT'], // Mr Slotty
            1064 => ['SLOT', 'FISHING'], // Net Game
            1067 => ['SLOT'], // Red Rake
            1062 => ['SLOT'], // Fazi
            1101 => ['SLOT'], // ZeusPlay
            1060 => ['SLOT'], // Volt Entertainment
            1148 => ['POKER', 'SLOT', 'P2P'], // WOW Gaming
            1149 => ['LIVE_CASINO', 'POKER'], // AI LIVE CASINO
            1022 => ['LIVE_CASINO'], // Sexy Gaming
            1033 => ['COCK_FIGHTING'], // SV388
            1139 => ['SLOT', 'FISHING'], // FASTSPIN
            1050 => ['SLOT'], // PlayStar
            1009 => ['SLOT', 'FISHING'], // CQ9
            1011 => ['LIVE_CASINO', 'SLOT'], // Play Tech
            1016 => ['LIVE_CASINO'], // YEE Bet
            1007 => ['SLOT'], // PG Soft (Direct)
            1046 => ['SPORT_BOOK', 'OTHERS', 'VIRTUAL_SPORT'], // IBC-SABA - Changed OTHERS
            1006 => ['LIVE_CASINO', 'SLOT', 'VIRTUAL_SPORT'], // Pragmatic Play
            1091 => ['SLOT', 'FISHING', 'LIVE_CASINO', 'POKER'], // jili
            1161 => ['SLOT', 'FISHING', 'LIVE_CASINO', 'POKER'], // Tada
            1012 => ['SPORT_BOOK', 'LIVE_CASINO', 'POKER', 'VIRTUAL_SPORT'], // SBO
            1052 => ['LIVE_CASINO'], // Dream Gaming
            1085 => ['SLOT', 'FISHING', 'OTHERS'], // JDB - Added OTHERS
            1049 => ['SLOT'], // Evoplay
            1040 => ['SPORT_BOOK'], // WBET
            1153 => ['SLOT'], // Hacksaw
            1154 => ['SLOT'], // Bigpot
            1018 => ['SLOT'], // Live22
            1157 => ['OTHERS'], // IMOON
            1004 => ['LIVE_CASINO', 'FISHING'], // Big Gaming
            1160 => ['SLOT'], // EPICWIN
            1163 => ['SLOT'], // NOVOMATIC
            1162 => ['SLOT'], // Octoplay
            1165 => ['OTHERS'], // aviatrix
            1164 => ['SPORT_BOOK'], // DIGITAIN
            1079 => ['SLOT', 'FISHING'], // Fachai
            1170 => ['SLOT'], // smartsoft
            1172 => ['LIVE_CASINO', 'SPORT_BOOK', 'SLOT', 'VIRTUAL_SPORT'], // World Entertainment
            1183 => ['SPORT_BOOK'], // FB SPORT
            1002 => ['LIVE_CASINO', 'OTHERS'], // Evolution（ASIA）
            1168 => ['SLOT'], // Netent（ASIA）
            1169 => ['SLOT'], // Red Tiger（ASIA）
            1166 => ['SLOT'], // no limit city （ASIA）
            1167 => ['SLOT'], // big time gaming （ASIA）
            1173 => ['LIVE_CASINO', 'OTHERS'], // Evolution (LATAM)
            1174 => ['SLOT'], // Netent (LATAM)
            1175 => ['SLOT'], // Red Tiger (LATAM)
            1176 => ['SLOT'], // no limit city (LATAM)
            1177 => ['SLOT'], // big time gaming(LATAM)
            1184 => ['SLOT'], // Rich88
            1185 => ['LIVE_CASINO'], // SA Gaming
            1115 => ['SLOT'], // BOOMING GAMES
            1192 => ['SLOT'], // AMIGO GAMING
            1193 => ['LIVE_CASINO'], // FB Games
            1197 => ['SLOT'], // Habanero
            1203 => ['SLOT', 'LIVE_CASINO'], // PlayAce
            1221 => ['SLOT', 'FISHING'], // SPADE GAMING
            1204 => ['SLOT'], // ADVANTPLAY
            1222 => ['ESPORT'], // TF Gaming
            1220 => ['LIVE_CASINO', 'OTHERS', 'POKER'], // ASTAR
        ];

        // Get the game type IDs from the database based on the codes.
        $gameTypes = DB::table('game_types')->get()->keyBy('code');

        // Get the product IDs from the database.
        $products = DB::table('products')->get()->keyBy('code');

        $gameTypeProductsData = [];

        // Iterate through the product mappings and create the insert data.
        foreach ($productGameTypes as $productCode => $gameTypeCodes) {
            // Ensure the product exists.
            if (isset($products[$productCode])) {
                $productId = $products[$productCode]->id;
                foreach ($gameTypeCodes as $gameTypeCode) {
                    // Ensure the game type exists.
                    if (isset($gameTypes[$gameTypeCode])) {
                        $gameTypeId = $gameTypes[$gameTypeCode]->id;
                         $gameTypeProductsData[] = [
                            'product_id' => $productId,
                            'game_type_id' => $gameTypeId,
                            'image' => 'default.png', // You can customize this
                            'rate' => rand(1, 100), // Or any logic to determine the rate
                            'created_at' => now(),
                            'updated_at' => now(),
                        ];
                    }
                }
            }
        }
        // Insert the data into the game_type_products table.
        DB::table('game_type_products')->insert($gameTypeProductsData);
    }
}
