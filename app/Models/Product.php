<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $fillable = ['code', 'name', 'short_name', 'order', 'status', 'game_list_status'];

    protected $appends = ['imgUrl']; // Changed from 'image' to 'imgUrl'
    //protected $appends = ['image'];

    public function gameTypes()
    {
        return $this->belongsToMany(GameType::class)->withPivot('image');
    }

//     public function gameTypes()
// {
//     return $this->belongsToMany(GameType::class, 'game_type_products')->withPivot('image');
// }


    // public function getImgUrlAttribute()
    // {
    //     if (isset($this->pivot) && isset($this->pivot->image)) {
    //         return asset('assets/img/game_logo/'.$this->pivot->image);
    //     }

    // }

    public function getImgUrlAttribute()
{
    if (isset($this->pivot) && !empty($this->pivot->image)) {
        return asset('assets/img/game_logo/' . $this->pivot->image);
    }

    // Optional: Return a default image if pivot image is missing
    return asset('assets/img/default.png'); // or null
}


    /**
     * Toggle the status between 1 and 0.
     *
     * @return bool
     */
    public function toggleStatus()
    {
        $this->status = $this->status == 1 ? 0 : 1;

        return $this->save();
    }
}