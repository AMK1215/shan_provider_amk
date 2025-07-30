<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "Checking all agents:\n";
$agents = User::where('type', 20)->orWhere('type', 30)->get();

foreach ($agents as $agent) {
    echo "Agent ID: " . $agent->id . "\n";
    echo "Username: " . $agent->user_name . "\n";
    echo "Type: " . $agent->type . "\n";
    echo "Shan Agent Code: " . $agent->shan_agent_code . "\n";
    echo "Shan Secret Key: " . ($agent->shan_secret_key ? 'Set' : 'Not Set') . "\n";
    echo "Shan Callback URL: " . ($agent->shan_callback_url ? 'Set' : 'Not Set') . "\n";
    echo "---\n";
}

echo "\nChecking players with agent_id:\n";
$players = User::whereNotNull('agent_id')->take(5)->get();

foreach ($players as $player) {
    echo "Player ID: " . $player->id . "\n";
    echo "Username: " . $player->user_name . "\n";
    echo "Agent ID: " . $player->agent_id . "\n";
    echo "Shan Agent Code: " . $player->shan_agent_code . "\n";
    echo "---\n";
} 