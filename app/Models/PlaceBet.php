<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaceBet extends Model
{
    use HasFactory;

    protected $table = 'place_bets';

    protected $fillable = [
        'member_account', 'product_code', 'provider_name', 'game_type', 'operator_code', 'request_time',
        'sign', 'currency', 'transaction_id', 'action', 'amount', 'valid_bet_amount',
        'bet_amount', 'prize_amount', 'tip_amount', 'wager_code', 'wager_status',
        'round_id', 'payload', 'settle_at', 'game_code', 'game_name', 'channel_code', 'status',
        'before_balance', 'balance',  
    ];

    protected $casts = [
        'payload' => 'array',
        'settle_at' => 'datetime',
        'request_time' => 'datetime',
    ];
} 