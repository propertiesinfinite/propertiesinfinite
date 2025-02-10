<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyAminity extends Model
{
    use HasFactory;

    public function aminity(){
        return $this->belongsTo(Aminity::class)->select('id','aminity');
    }

    protected $casts =  [
        'id' => 'integer',
        'aminity_id' => 'integer',
        'property_id' => 'integer',
    ];

}
