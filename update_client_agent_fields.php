<?php

require_once 'vendor/autoload.php';

use App\Models\User;
use Illuminate\Support\Facades\DB;

// Bootstrap Laravel
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "Updating client_agent_name and client_agent_id fields...\n";

try {
    // Update P0101 (ID 16) - should have agent_id 13 and client_agent_name 'AG17830260'
    $player1 = User::find(16); // P0101
    if ($player1) {
        $player1->update([
            'client_agent_name' => 'AG17830260',
            'client_agent_id' => 13,
        ]);
        echo "Updated P0101 (ID 16): client_agent_name = AG17830260, client_agent_id = 13\n";
    }

    // Update P0102 (ID 14) - should have agent_id 13 and client_agent_name 'AG17830260'
    $player2 = User::find(14); // P0102
    if ($player2) {
        $player2->update([
            'client_agent_name' => 'AG17830260',
            'client_agent_id' => 13,
        ]);
        echo "Updated P0102 (ID 14): client_agent_name = AG17830260, client_agent_id = 13\n";
    }

    // Verify the updates
    echo "\nVerifying updates:\n";
    
    $player1 = User::find(16);
    if ($player1) {
        echo "P0101 (ID 16): agent_id = {$player1->agent_id}, client_agent_name = {$player1->client_agent_name}, client_agent_id = {$player1->client_agent_id}\n";
    }
    
    $player2 = User::find(14);
    if ($player2) {
        echo "P0102 (ID 14): agent_id = {$player2->agent_id}, client_agent_name = {$player2->client_agent_name}, client_agent_id = {$player2->client_agent_id}\n";
    }

    // Check agent details
    $agent13 = User::find(13);
    if ($agent13) {
        echo "Agent 13: user_name = {$agent13->user_name}, shan_agent_code = {$agent13->shan_agent_code}, type = {$agent13->type}\n";
    }

    echo "\nUpdate completed successfully!\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 