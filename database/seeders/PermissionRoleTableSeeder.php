<?php

namespace Database\Seeders;

use App\Models\Admin\Permission;
use App\Models\Admin\Role;
use Illuminate\Database\Seeder;

class PermissionRoleTableSeeder extends Seeder
{
    public function run(): void
    {
        // Role IDs: 1 = Owner, 2 = Master, 3 = Agent, 4 = SubAgent, 5 = Player, 6 = SystemWallet

        $ownerPermissions = Permission::whereIn('title', [
            'owner_access',
            'master_index', 'master_create', 'master_edit', 'master_delete',
            'transfer_log', 'make_transfer',
            'game_type_access',
        ])->pluck('id');
        Role::findOrFail(1)->permissions()->sync($ownerPermissions);

        $masterPermissions = Permission::whereIn('title', [
            'master_access',
            'agent_index', 'agent_create', 'agent_edit', 'agent_delete',
            'transfer_log', 'make_transfer',
        ])->pluck('id');
        Role::findOrFail(2)->permissions()->sync($masterPermissions);

        $agentPermissions = Permission::whereIn('title', [
            'agent_access',
            'subagent_index', 'subagent_create', 'subagent_edit', 'subagent_delete',
            'transfer_log', 'make_transfer',
        ])->pluck('id');
        Role::findOrFail(3)->permissions()->sync($agentPermissions);

        $subAgentPermissions = Permission::whereIn('title', [
            'subagent_access',
            'player_index', 'player_create', 'player_edit', 'player_delete',
            'transfer_log', 'make_transfer',
        ])->pluck('id');
        Role::findOrFail(4)->permissions()->sync($subAgentPermissions);

        $playerPermissions = Permission::whereIn('title', [
            'player_access', 'withdraw', 'deposit', 'bank', 'contact',
        ])->pluck('id');
        Role::findOrFail(5)->permissions()->sync($playerPermissions);

        $systemWalletPermission = Permission::where('title', 'system_wallet')->first();
        Role::findOrFail(6)->permissions()->sync($systemWalletPermission ? [$systemWalletPermission->id] : []);
    }
}
