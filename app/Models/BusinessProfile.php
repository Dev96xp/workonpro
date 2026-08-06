<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BusinessProfile extends Model
{
    protected $table = 'business_profile';

    protected $fillable = [
        'business_name',
        'business_title',
        'show_logo_on_hero',
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
        'youtube',
        'x',
        'tiktok',
        'discord',
        'business_hours',
        'has_license',
        'license_number',
        'has_insurance',
        'insurance_number',
    ];

    protected function casts(): array
    {
        return [
            'show_logo_on_hero' => 'boolean',
            'has_license' => 'boolean',
            'has_insurance' => 'boolean',
        ];
    }
}
