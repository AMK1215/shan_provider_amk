<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GameList extends Model
{
    use HasFactory;

    protected $fillable = [
        'game_code',
        'game_name',
        'game_type',
        'image_url',
        'product_id',
        'product_code',
        'support_currency',
        'status',
    ];
}
