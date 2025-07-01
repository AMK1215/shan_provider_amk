<?php

namespace App\Models\TwoDigit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TwoDLimit extends Model
{
    use HasFactory;
    protected $table = 'two_d_limits';

    protected $fillable = [
        'two_d_limit',
    ];

    public function scopeLasted($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}
