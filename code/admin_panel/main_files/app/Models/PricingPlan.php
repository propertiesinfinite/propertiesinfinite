<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PricingPlan extends Model
{
    use HasFactory;

    protected $casts =  [
        'id' => 'integer',
        'plan_price' => 'double',
        'number_of_property' => 'integer',
        'featured_property_qty' => 'integer',
        'top_property_qty' => 'integer',
        'urgent_property_qty' => 'integer',
        'serial' => 'integer',
    ];
}
