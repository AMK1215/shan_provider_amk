<?php

require_once 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

echo "Fixing player agent relationships...\n";

// Get all players with incorrect agent_id (14 is actually a player, not an agent)
$playersWithIncorrectAgent = User::where('agent_id', 14)->get();

foreach ($playersWithIncorrectAgent as $player) {
    echo "Fixing player: " . $player->user_name . " (ID: " . $player->id . ")\n";
    echo "Current agent_id: " . $player->agent_id . "\n";
    
    // Get the correct agent (ID 13 - AG61735374)
    $correctAgent = User::find(13);
    
    if ($correctAgent && $correctAgent->type == 20) {
        $player->agent_id = $correctAgent->id;
        $player->save();
        
        echo "Updated agent_id to: " . $correctAgent->id . " (" . $correctAgent->user_name . ")\n";
        echo "Agent Shan Code: " . $correctAgent->shan_agent_code . "\n";
        echo "Agent Secret Key: " . ($correctAgent->shan_secret_key ? 'Set' : 'Not Set') . "\n";
        echo "Agent Callback URL: " . ($correctAgent->shan_callback_url ? 'Set' : 'Not Set') . "\n";
    } else {
        echo "No valid agent found to assign!\n";
    }
    echo "---\n";
}

echo "\nChecking all players with agent_id:\n";
$allPlayers = User::whereNotNull('agent_id')->get();

foreach ($allPlayers as $player) {
    $agent = User::find($player->agent_id);
    echo "Player: " . $player->user_name . " (ID: " . $player->id . ")\n";
    echo "Agent ID: " . $player->agent_id . "\n";
    echo "Agent Username: " . ($agent ? $agent->user_name : 'NOT FOUND') . "\n";
    echo "Agent Type: " . ($agent ? $agent->type : 'N/A') . "\n";
    echo "---\n";
}

echo "\nChecking all agents:\n";
$agents = User::where('type', 20)->get();

foreach ($agents as $agent) {
    echo "Agent ID: " . $agent->id . "\n";
    echo "Username: " . $agent->user_name . "\n";
    echo "Type: " . $agent->type . "\n";
    echo "Shan Agent Code: " . $agent->shan_agent_code . "\n";
    echo "Shan Secret Key: " . ($agent->shan_secret_key ? 'Set' : 'Not Set') . "\n";
    echo "Shan Callback URL: " . ($agent->shan_callback_url ? 'Set' : 'Not Set') . "\n";
    echo "---\n";
} 