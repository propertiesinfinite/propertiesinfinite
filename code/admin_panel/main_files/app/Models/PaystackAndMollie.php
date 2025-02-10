<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaystackAndMollie extends Model
{
    use HasFactory;

    protected $casts =  [
        'id' => 'integer',
        'mollie_status' => 'integer',
        'paystack_status' => 'integer'
    ];
}
