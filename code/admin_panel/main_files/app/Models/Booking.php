<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    use HasFactory;

    public function property(){
        return $this->belongsTo(Property::class);
    }

    public function property_name(){
        return $this->belongsTo(Property::class, 'property_id','id');
    }

    public function user(){
        return $this->belongsTo(User::class,'user_id')->select('id','name','email','image','phone','designation','status','address','created_at');
    }
}
