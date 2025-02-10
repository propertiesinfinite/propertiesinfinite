<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BreadcrumbImage extends Model
{
    use HasFactory;

    protected $casts =  [
        'id' => 'integer',
        'image_type' => 'integer'
    ];
}
