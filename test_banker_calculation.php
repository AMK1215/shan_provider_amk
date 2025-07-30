<?php

// Test the banker calculation logic
echo "Testing Banker Calculation Logic:\n\n";

// Test 1: Player wins 50
echo "Test 1: Player wins 50\n";
$amountChanged = 50;
$winLoseStatus = 1; // Player wins
$playerNetAmount = $winLoseStatus == 1 ? $amountChanged : -$amountChanged;
$totalPlayerNet = $playerNetAmount;
$bankerAmountChange = -$totalPlayerNet;

echo "amount_changed: $amountChanged\n";
echo "win_lose_status: $winLoseStatus\n";
echo "playerNetAmount: $playerNetAmount\n";
echo "totalPlayerNet: $totalPlayerNet\n";
echo "bankerAmountChange: $bankerAmountChange\n";
echo "banker_gains: " . ($bankerAmountChange > 0 ? 'Yes' : 'No') . "\n";
echo "Expected: Player gains 50, Banker loses 50\n";
echo "Result: " . ($bankerAmountChange == -50 ? 'CORRECT' : 'WRONG') . "\n\n";

// Test 2: Player loses 900
echo "Test 2: Player loses 900\n";
$amountChanged = 900;
$winLoseStatus = 0; // Player loses
$playerNetAmount = $winLoseStatus == 1 ? $amountChanged : -$amountChanged;
$totalPlayerNet = $playerNetAmount;
$bankerAmountChange = -$totalPlayerNet;

echo "amount_changed: $amountChanged\n";
echo "win_lose_status: $winLoseStatus\n";
echo "playerNetAmount: $playerNetAmount\n";
echo "totalPlayerNet: $totalPlayerNet\n";
echo "bankerAmountChange: $bankerAmountChange\n";
echo "banker_gains: " . ($bankerAmountChange > 0 ? 'Yes' : 'No') . "\n";
echo "Expected: Player loses 900, Banker gains 900\n";
echo "Result: " . ($bankerAmountChange == 900 ? 'CORRECT' : 'WRONG') . "\n\n";

// Test 3: Multiple players
echo "Test 3: Multiple players\n";
$players = [
    ['amount_changed' => 100, 'win_lose_status' => 1], // Player 1 wins 100
    ['amount_changed' => 200, 'win_lose_status' => 0], // Player 2 loses 200
];

$totalPlayerNet = 0;
foreach ($players as $player) {
    $amountChanged = $player['amount_changed'];
    $winLoseStatus = $player['win_lose_status'];
    $playerNetAmount = $winLoseStatus == 1 ? $amountChanged : -$amountChanged;
    $totalPlayerNet += $playerNetAmount;
}

$bankerAmountChange = -$totalPlayerNet;

echo "Player 1: wins 100 (net: +100)\n";
echo "Player 2: loses 200 (net: -200)\n";
echo "totalPlayerNet: $totalPlayerNet\n";
echo "bankerAmountChange: $bankerAmountChange\n";
echo "banker_gains: " . ($bankerAmountChange > 0 ? 'Yes' : 'No') . "\n";
echo "Expected: Net player change -100, Banker gains 100\n";
echo "Result: " . ($bankerAmountChange == 100 ? 'CORRECT' : 'WRONG') . "\n"; 