<?php

namespace App\Models;

use App\Models\Country;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class City extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'slug', 'country_id'
    ];

    protected $appends = ['totalProperty'];

    public function getTotalPropertyAttribute()
    {
        return $this->properties()->count();
    }

    public function properties(){
        return $this->hasMany(Property::class, 'city_id')->where(function ($query) {
            $query->where('expired_date', null)
                ->orWhere('expired_date', '>=', date('Y-m-d'));
        })->where('approve_by_admin', 'approved')->where('status', 'enable');
    }

    public function country(){
        return $this->belongsTo(Country::class);
    }

    protected $casts =  [
        'id' => 'integer',
        'totalProperty' => 'integer',
        'show_homepage' => 'integer',
        'serial' => 'integer',
    ];

}
