<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyPlan extends Model
{
    use HasFactory;

    protected $casts =  [
        'id' => 'integer',
        'property_id' => 'integer',
    ];
}
