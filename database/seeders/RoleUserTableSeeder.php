<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class RoleUserTableSeeder extends Seeder
{
    public function run(): void
    {
        User::findOrFail(1)->roles()->sync(1); // Owner
        User::findOrFail(2)->roles()->sync(2); // Master
        User::findOrFail(3)->roles()->sync(3); // Agent
        User::findOrFail(4)->roles()->sync(4); // SubAgent
        User::findOrFail(5)->roles()->sync(5); // Player
        User::findOrFail(6)->roles()->sync(6); // SystemWallet
        User::findOrFail(7)->roles()->sync(5); // Player
        User::findOrFail(8)->roles()->sync(5); // Player
        User::findOrFail(9)->roles()->sync(5); // Player
        User::findOrFail(10)->roles()->sync(5); // Player
        //User::findOrFail(11)->roles()->sync(5); // Player
    }
}
