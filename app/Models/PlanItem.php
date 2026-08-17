<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class PlanItem extends Model
{
    use CentralConnection;

    /**
     * Claves de recurso reconocidas por el código de enforcement
     * (services.blade.php, coupons.blade.php, images.blade.php, ImageController,
     * Tenant::hasFeature() para invoices/products/product-categories/appointments/employees).
     */
    public const RESOURCE_LABELS = [
        'images' => 'Imágenes',
        'services' => 'Servicios',
        'coupons' => 'Cupones',
        'invoices' => 'Facturación',
        'appointments' => 'Citas',
        'employees' => 'Empleados',
    ];

    protected $fillable = [
        'plan_id',
        'name',
        'quantity',
    ];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function label(): string
    {
        return self::RESOURCE_LABELS[$this->name] ?? $this->name;
    }
}
