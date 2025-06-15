<?php

namespace App\Models\Admin;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReportTransaction extends Model
{
    use HasFactory;

    protected $fillable = ['game_type_id', 'user_id', 'rate', 'status', 'transaction_amount', 'bet_amount', 'valid_amount', 'payout', 'final_turn', 'banker'];
}
