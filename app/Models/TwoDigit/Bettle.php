<?php

namespace App\Models\TwoDigit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Bettle extends Model
{
    use HasFactory;

    protected $table = 'battles';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = ['battle_name', 'start_time', 'end_time', 'status', 'open_date'];

}
