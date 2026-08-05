<?php

namespace App\Models;

use App\Enums\ServiceCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class ServiceListing extends Model
{
    use CentralConnection;

    protected $fillable = [
        'tenant_id',
        'service_id',
        'name',
        'description',
        'price',
        'category',
        'city',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'category' => ServiceCategory::class,
            'price' => 'decimal:2',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
