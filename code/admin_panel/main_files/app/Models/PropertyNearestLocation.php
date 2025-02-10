<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PropertyNearestLocation extends Model
{
    use HasFactory;

    public function location(){
        return $this->belongsTo(NearestLocation::class, 'nearest_location_id')->where('status', 1)->select('id','location','status');
    }

    protected $casts =  [
        'id' => 'integer',
        'nearest_location_id' => 'integer',
        'property_id' => 'integer',
    ];
}
