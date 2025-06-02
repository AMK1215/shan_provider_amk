<?php

namespace Database\Seeders;

use App\Enums\TransactionName;
use App\Enums\UserType;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $walletService = new WalletService;

        // Create owner with large initial capital
        $owner = $this->createUser(
            UserType::Owner,
            'Owner',
            'OWNER001',
            '09123456789',
            null,
            'OWNER'.Str::random(6)
        );
        $walletService->deposit($owner, 500_000_00000000, TransactionName::CapitalDeposit);

        // Create system wallet
        $systemWallet = $this->createUser(
            UserType::SystemWallet,
            'System Wallet',
            'SYS001',
            '09222222222',
            null,
            'SYS'.Str::random(6)
        );
        $walletService->deposit($systemWallet, 500 * 100_0000, TransactionName::CapitalDeposit);

        // Create 10 agents
        for ($i = 1; $i <= 2; $i++) {
            $agent = $this->createUser(
                UserType::Agent,
                "Agent $i",
                'AGENT'.str_pad($i, 3, '0', STR_PAD_LEFT),
                '091123456'.str_pad($i, 2, '0', STR_PAD_LEFT),
                $owner->id,
                'AGENT'.Str::random(6)
            );
            // Random initial balance between 1.5M to 2.5M
            $initialBalance = 4 * 100_000;
            $walletService->transfer($owner, $agent, $initialBalance, TransactionName::CreditTransfer);

            // Create 10 sub-agents for each agent
            for ($j = 1; $j <= 2; $j++) {
                $subAgent = $this->createUser(
                    UserType::SubAgent,
                    "SubAgent $i-$j",
                    'SUB'.str_pad($i, 2, '0', STR_PAD_LEFT).str_pad($j, 2, '0', STR_PAD_LEFT),
                    '091765432'.str_pad($i, 1, '0', STR_PAD_LEFT).str_pad($j, 1, '0', STR_PAD_LEFT),
                    $agent->id,
                    'SUB'.Str::random(6)
                );
                // Random initial balance between 800K to 1.2M
                $initialBalance = 2 * 100_000;
                $walletService->transfer($agent, $subAgent, $initialBalance, TransactionName::CreditTransfer);

                // Create 10 players for each sub-agent
                for ($k = 1; $k <= 2; $k++) {
                    $player = $this->createUser(
                        UserType::Player,
                        "Player $i-$j-$k",
                        'PLAYER'.str_pad($i, 2, '0', STR_PAD_LEFT).str_pad($j, 2, '0', STR_PAD_LEFT).str_pad($k, 2, '0', STR_PAD_LEFT),
                        '091111111'.str_pad($i, 1, '0', STR_PAD_LEFT).str_pad($j, 1, '0', STR_PAD_LEFT).str_pad($k, 1, '0', STR_PAD_LEFT),
                        $subAgent->id,
                        'PLAYER'.Str::random(6)
                    );
                    // Random initial balance between 4K to 6K
                    $initialBalance = 2 * 100_00;
                    $walletService->transfer($subAgent, $player, $initialBalance, TransactionName::CreditTransfer);
                }
            }
        }
    }

    private function createUser(
        UserType $type,
        string $name,
        string $user_name,
        string $phone,
        ?int $parent_id = null,
        ?string $referral_code = null
    ): User {
        return User::create([
            'name' => $name,
            'user_name' => $user_name,
            'phone' => $phone,
            'password' => Hash::make('gscplus'),
            'agent_id' => $parent_id,
            'status' => 1,
            'is_changed_password' => 1,
            'type' => $type->value,
            'referral_code' => $referral_code,
        ]);
    }
}
