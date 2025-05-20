<?php

namespace Database\Seeders;

use App\Enums\TransactionName;
use App\Enums\UserType;
use App\Models\User;
use App\Services\WalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersTableSeeder extends Seeder
{
    public function run(): void
    {
        $walletService = new WalletService();

        $owner = $this->createUser(UserType::Owner, 'Owner', 'O3456454', '09123456789');
        $walletService->deposit($owner, 500_000_00000000, TransactionName::CapitalDeposit);

        $master = $this->createUser(UserType::Master, 'Master', 'M3456454', '09123456780', $owner->id);
        $walletService->transfer($owner, $master, 5 * 100_0000000, TransactionName::CreditTransfer);

        $agent = $this->createUser(UserType::Agent, 'Agent 1', 'A898737', '09112345674', $master->id, 'vH6u5E9');
        $walletService->transfer($master, $agent, 2 * 100_000000, TransactionName::CreditTransfer);

        $subAgent = $this->createUser(UserType::SubAgent, 'SubAgent', 'SA123456', '09176543210', $agent->id);
        $walletService->transfer($agent, $subAgent, 1 * 100_00000, TransactionName::CreditTransfer);

        $player = $this->createUser(UserType::Player, 'Player 1', 'Player001', '09111111111', $subAgent->id);
        $walletService->transfer($subAgent, $player, 5 * 100_00, TransactionName::CreditTransfer);

        $systemWallet = $this->createUser(UserType::SystemWallet, 'SystemWallet', 'systemWallet', '09222222222');
        $walletService->deposit($systemWallet, 500 * 100_0000, TransactionName::CapitalDeposit);

        // More players
        foreach (range(2, 5) as $i) {
            $player = $this->createUser(
                UserType::Player,
                "Player $i",
                'Player00' . $i,
                '0911111111' . $i,
                $subAgent->id
            );
            $walletService->transfer($subAgent, $player, 5 * 100_00, TransactionName::CreditTransfer);
        }
    }

    private function createUser(
        UserType $type,
        string $name,
        string $user_name,
        string $phone,
        int $parent_id = null,
        string $referral_code = null
    ): User {
        return User::create([
            'name' => $name,
            'user_name' => $user_name,
            'phone' => $phone,
            'password' => Hash::make('delightmyanmar'),
            'agent_id' => $parent_id,
            'status' => 1,
            'is_changed_password' => 1,
            'type' => $type->value,
            'referral_code' => $referral_code,
        ]);
    }
}
