<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\GameType;

class ReportTransaction extends Model
{
    use HasFactory;
    protected $table = 'report_transactions';

    protected $fillable = ['game_type_id', 'user_id', 'rate', 'status', 'transaction_amount', 'bet_amount', 'valid_amount', 'payout', 'final_turn', 'banker']; 

    protected $casts = [
        'rate' => 'decimal:2',
        'transaction_amount' => 'decimal:2',
        'bet_amount' => 'decimal:2',
        'valid_amount' => 'decimal:2',
        'payout' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function gameType()
    {
        return $this->belongsTo(GameType::class);
    }

}

