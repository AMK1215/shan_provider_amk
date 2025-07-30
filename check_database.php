<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "Checking database contents:\n";

// Check total users
$totalUsers = User::count();
echo "Total users: " . $totalUsers . "\n";

// Check all users
echo "\nAll users:\n";
$users = User::all();

foreach ($users as $user) {
    echo "ID: " . $user->id . ", Username: " . $user->user_name . ", Type: " . $user->type . "\n";
}

// Check agents
echo "\nAgents (type 20):\n";
$agents = User::where('type', 20)->get();

foreach ($agents as $agent) {
    echo "Agent ID: " . $agent->id . ", Username: " . $agent->user_name . "\n";
    echo "Shan Agent Code: " . $agent->shan_agent_code . "\n";
    echo "Shan Secret Key: " . ($agent->shan_secret_key ? 'Set' : 'Not Set') . "\n";
    echo "Shan Callback URL: " . ($agent->shan_callback_url ? 'Set' : 'Not Set') . "\n";
    echo "---\n";
}

// Check players
echo "\nPlayers (type 40):\n";
$players = User::where('type', 40)->get();

foreach ($players as $player) {
    echo "Player ID: " . $player->id . ", Username: " . $player->user_name . "\n";
    echo "Agent ID: " . $player->agent_id . "\n";
    echo "Shan Agent Code: " . $player->shan_agent_code . "\n";
    echo "---\n";
} 