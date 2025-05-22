<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlaceBet extends Model
{
    use HasFactory;

    protected $table = 'place_bets';

    protected $fillable = [
        'transaction_id',
        'member_account',
        'product_code',
        'amount',
        'action',
        'status',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
} 