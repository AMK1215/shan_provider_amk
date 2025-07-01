<?php

namespace App\Models\TwoDigit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TwoDigit\TwoBet;

class ChooseDigit extends Model
{
    use HasFactory;
    protected $fillable = ['choose_close_digit', 'status'];


    public function twoBets()
{
    return $this->hasMany(TwoBet::class, 'choose_digit_id');
}
}
