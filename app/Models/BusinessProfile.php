<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessProfile extends Model
{
    protected $table = 'business_profile';

    protected $fillable = [
        'business_name',
        'slogan',
        'description',
        'policy',
        'objectives',
        'email',
        'phone',
        'phone_2',
        'whatsapp',
        'address',
        'city',
        'country',
        'website',
        'instagram',
        'facebook',
        'business_hours',
    ];
}
