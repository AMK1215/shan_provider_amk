<?php
namespace App\Models\TwoDigit;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HeadClose extends Model
{
    use HasFactory;
    protected $fillable = ['head_close_digit', 'status'];
}
