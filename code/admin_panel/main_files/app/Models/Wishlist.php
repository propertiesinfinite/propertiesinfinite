<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wishlist extends Model
{
    use HasFactory;

    protected $casts =  [
        'id' => 'integer',
        'property_id' => 'integer',
        'user_id' => 'integer',
    ];
}
