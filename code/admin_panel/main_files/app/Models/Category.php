<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;


    protected $appends = ['totalProperty'];

    public function getTotalPropertyAttribute()
    {
        return $this->properties()->count();
    }

    public function properties(){
        return $this->hasMany(Property::class, 'property_type_id')->where(function ($query) {
            $query->where('expired_date', null)
                ->orWhere('expired_date', '>=', date('Y-m-d'));
        })->where('approve_by_admin', 'approved')->where('status', 'enable');;
    }


    protected $casts =  [
        'id' => 'integer',
        'status' => 'integer',
        'totalProperty' => 'integer',
    ];


}
