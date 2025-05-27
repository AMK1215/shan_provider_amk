<?php

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\Admin\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoleUserTableSeeder extends Seeder
{
    private array $roleMap = [
        UserType::Owner->value => 'Owner',
        UserType::Agent->value => 'Agent',
        UserType::SubAgent->value => 'SubAgent',
        UserType::Player->value => 'Player',
        UserType::SystemWallet->value => 'SystemWallet',
    ];

    public function run(): void
    {
        try {
            DB::beginTransaction();

            // Validate roles exist
            $this->validateRoles();

            // Clean up existing role assignments
            $this->cleanupExistingAssignments();

            // Get all roles
            $roles = Role::all()->pluck('id', 'title')->toArray();
            $totalUsers = 0;
            $successCount = 0;

            foreach ($this->roleMap as $userType => $roleTitle) {
                $roleId = $roles[$roleTitle];
                $users = User::where('type', $userType)->get();
                $totalUsers += $users->count();

                if ($users->isEmpty()) {
                    Log::warning("No users found for type: {$userType}");
                    continue;
                }

                // Bulk assign roles
                $users->each(function ($user) use ($roleId, $roleTitle, &$successCount) {
                    try {
                        $user->roles()->sync($roleId);
                        $successCount++;
                        Log::info("Assigned role '{$roleTitle}' to user: {$user->user_name}");
                    } catch (\Exception $e) {
                        Log::error("Failed to assign role '{$roleTitle}' to user {$user->user_name}: " . $e->getMessage());
                    }
                });

                Log::info("Successfully assigned '{$roleTitle}' role to {$users->count()} users");
            }

            // Verify role assignments
            $this->verifyRoleAssignments($totalUsers, $successCount);

            DB::commit();
            Log::info("Role assignment completed successfully. Total users: {$totalUsers}, Successful assignments: {$successCount}");

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error in RoleUserTableSeeder: " . $e->getMessage());
            throw $e;
        }
    }

    private function validateRoles(): void
    {
        $requiredRoles = array_values($this->roleMap);
        $existingRoles = Role::whereIn('title', $requiredRoles)->pluck('title')->toArray();
        $missingRoles = array_diff($requiredRoles, $existingRoles);

        if (!empty($missingRoles)) {
            throw new \RuntimeException("Missing required roles: " . implode(', ', $missingRoles));
        }
    }

    private function cleanupExistingAssignments(): void
    {
        try {
            DB::table('role_user')->truncate();
            Log::info("Cleaned up existing role assignments");
        } catch (\Exception $e) {
            Log::error("Failed to cleanup existing role assignments: " . $e->getMessage());
            throw $e;
        }
    }

    private function verifyRoleAssignments(int $totalUsers, int $successCount): void
    {
        if ($successCount !== $totalUsers) {
            Log::warning("Role assignment verification failed. Expected: {$totalUsers}, Actual: {$successCount}");
            throw new \RuntimeException("Role assignment verification failed. Some users may not have received their roles.");
        }

        // Verify each user has exactly one role
        $usersWithMultipleRoles = DB::table('role_user')
            ->select('user_id', DB::raw('COUNT(*) as role_count'))
            ->groupBy('user_id')
            ->having('role_count', '>', 1)
            ->get();

        if ($usersWithMultipleRoles->isNotEmpty()) {
            Log::error("Found users with multiple roles: " . $usersWithMultipleRoles->pluck('user_id')->implode(', '));
            throw new \RuntimeException("Some users have multiple roles assigned. This should not happen.");
        }
    }
}
