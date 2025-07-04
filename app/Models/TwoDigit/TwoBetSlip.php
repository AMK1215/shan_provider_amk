<?php

namespace App\Models\TwoDigit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
use App\Models\TwoDigit\TwoBet;

class TwoBetSlip extends Model
{
    use HasFactory;
    protected $table = 'two_bet_slips';
    protected $fillable = [
        'slip_no',
        'user_id',
        'total_bet_amount',
        'session',
        'status',
        'before_balance',
        'after_balance',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function twoBets()
    {
        return $this->hasMany(TwoBet::class);
    }
}
