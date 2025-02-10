<?php

namespace App\Models;

use Modules\Kyc\Entities\KycType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CompanyProfile extends Model
{
    use HasFactory;

    public function kyc(){
        return $this->belongsTo(KycType::class);
    }
}
