<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PermissionsTableSeeder::class,
            RolesTableSeeder::class,
            PermissionRoleTableSeeder::class,
            UsersTableSeeder::class,
            RoleUserTableSeeder::class,
            BannerSeeder::class,
            BannerTextSeeder::class,
            BannerAdsSeeder::class,
            PaymentTypeTableSeeder::class,
            BankTableSeeder::class,
            WinnerTextSeeder::class,
            TopTenWithdrawSeeder::class,
            ContactTypeSeeder::class,
            ContactSeeder::class,
            PromotionSeeder::class,
            AdsVedioSeeder::class,
            PoneWineBetsTableSeeder::class, 
            PoneWinePlayerBetsTableSeeder::class, 
            PoneWineBetInfosTableSeeder::class,

        ]);
    }
}
