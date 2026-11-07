<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stancl\Tenancy\Database\Concerns\CentralConnection;

class Plan extends Model
{
    use CentralConnection;

    protected $fillable = [
        'name',
        'slug',
        'price',
        'old_price',
        'stripe_price_id',
        'sort_order',
        'is_active',
    ];

    /**
     * Cantidad máxima de planes que pueden estar activos (habilitados para
     * asignarse a tenants) al mismo tiempo.
     */
    public const MAX_ACTIVE = 3;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'price' => 'decimal:2',
            'old_price' => 'decimal:2',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(PlanItem::class);
    }

    /**
     * Un plan con precio 0 se activa sin pasar por Stripe (sirve para promociones gratis).
     */
    public function isFree(): bool
    {
        return (float) $this->price <= 0;
    }

    /**
     * Precio de Stripe con el que se cobra el plan: el creado desde el admin o, si
     * todavía no hay uno, el configurado en el .env para ese slug.
     */
    public function checkoutPriceId(): ?string
    {
        return $this->stripe_price_id ?: config('services.stripe.prices.'.$this->slug);
    }

    public function displayPrice(): string
    {
        return $this->isFree() ? __('register.plans.free') : self::money($this->price);
    }

    public function displayOldPrice(): ?string
    {
        return $this->old_price !== null && (float) $this->old_price > 0 ? self::money($this->old_price) : null;
    }

    public function priceInCents(): int
    {
        return (int) round((float) $this->price * 100);
    }

    private static function money(string|float $amount): string
    {
        $amount = (float) $amount;

        return '$'.(fmod($amount, 1.0) === 0.0 ? number_format($amount) : number_format($amount, 2));
    }
}
