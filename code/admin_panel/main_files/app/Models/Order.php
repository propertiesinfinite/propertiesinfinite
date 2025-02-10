<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;


    public function agent(){
        return $this->belongsTo(User::class,'agent_id')->select('id','name','email','image','phone','address');
    }

    protected $casts =  [
        'id' => 'integer',
        'agent_id' => 'integer',
        'pricing_plan_id' => 'integer',
    ];

}

