<?php

namespace App\Models\TwoDigit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChooseDigit extends Model
{
    use HasFactory;
    protected $fillable = ['choose_close_digit', 'status'];
}
