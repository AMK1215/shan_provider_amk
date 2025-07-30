<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "Checking specific players:\n";

// Check P0101
$player1 = User::where('user_name', 'P0101')->first();
if ($player1) {
    echo "P0101 found:\n";
    echo "ID: " . $player1->id . "\n";
    echo "Username: " . $player1->user_name . "\n";
    echo "Agent ID: " . $player1->agent_id . "\n";
    echo "Shan Agent Code: " . $player1->shan_agent_code . "\n";
    
    $agent1 = User::find($player1->agent_id);
    echo "Agent Username: " . ($agent1 ? $agent1->user_name : 'NOT FOUND') . "\n";
    echo "Agent Type: " . ($agent1 ? $agent1->type : 'N/A') . "\n";
    echo "---\n";
} else {
    echo "P0101 not found\n";
}

// Check P0104
$player2 = User::where('user_name', 'P0104')->first();
if ($player2) {
    echo "P0104 found:\n";
    echo "ID: " . $player2->id . "\n";
    echo "Username: " . $player2->user_name . "\n";
    echo "Agent ID: " . $player2->agent_id . "\n";
    echo "Shan Agent Code: " . $player2->shan_agent_code . "\n";
    
    $agent2 = User::find($player2->agent_id);
    echo "Agent Username: " . ($agent2 ? $agent2->user_name : 'NOT FOUND') . "\n";
    echo "Agent Type: " . ($agent2 ? $agent2->type : 'N/A') . "\n";
    echo "---\n";
} else {
    echo "P0104 not found\n";
}

// Check all players with shan_agent_code A3H4
echo "\nChecking players with shan_agent_code A3H4:\n";
$playersWithCode = User::where('shan_agent_code', 'A3H4')->get();

foreach ($playersWithCode as $player) {
    echo "Player: " . $player->user_name . " (ID: " . $player->id . ")\n";
    echo "Type: " . $player->type . "\n";
    echo "Agent ID: " . $player->agent_id . "\n";
    echo "---\n";
}

// Check agent ID 13
echo "\nChecking Agent ID 13:\n";
$agent13 = User::find(13);
if ($agent13) {
    echo "Agent ID 13 found:\n";
    echo "Username: " . $agent13->user_name . "\n";
    echo "Type: " . $agent13->type . "\n";
    echo "Shan Agent Code: " . $agent13->shan_agent_code . "\n";
    echo "Shan Secret Key: " . ($agent13->shan_secret_key ? 'Set' : 'Not Set') . "\n";
    echo "Shan Callback URL: " . ($agent13->shan_callback_url ? 'Set' : 'Not Set') . "\n";
} else {
    echo "Agent ID 13 not found\n";
} 