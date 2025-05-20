<?php

namespace Database\Seeders;

use App\Models\Admin\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class PermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            [
                'title' => 'senior_owner_access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'owner_access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'super_access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'senior_access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'master_access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'agent_access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'player_index',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'player_create',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'player_edit',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'player_delete',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'agent_index',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'agent_create',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'agent_edit',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'agent_delete',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'agent_change_password_access',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'transfer_log',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'make_transfer',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'bank',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'withdraw',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'deposit',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'contact',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'owner_index',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'owner_create',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'owner_edit',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'owner_delete',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'master_index',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'master_create',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'master_edit',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'master_delete',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'super_index',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'super_create',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'super_edit',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'super_delete',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'senior_index',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'senior_create',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'senior_edit',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'senior_delete',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'title' => 'system_wallet',
                'created_at' => now(),
                'updated_at' => now(),
            ],

        ];

        Permission::insert($permissions);
    }
}
