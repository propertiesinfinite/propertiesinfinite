<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;


    public function user(){
        return $this->belongsTo(User::class,'user_id')->select('id','name','email','image','phone','designation','status','address','created_at');
    }

    public function property(){
        return $this->belongsTo(Property::class)->select('id','title','slug','thumbnail_image','agent_id');
    }

    protected $casts =  [
        'id' => 'integer',
        'property_id' => 'integer',
        'user_id' => 'integer',
        'agent_id' => 'integer',
        'rating' => 'integer',
        'status' => 'integer',
    ];



}
