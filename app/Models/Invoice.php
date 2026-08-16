<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    protected $fillable = [
        'client_id',
        'description',
        'notes',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(InvoiceItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function taxes(): HasMany
    {
        return $this->hasMany(InvoiceTax::class);
    }

    /**
     * Suma de las líneas de producto, antes de impuestos.
     */
    public function subtotal(): float
    {
        return (float) $this->items->sum(fn (InvoiceItem $item) => $item->quantity * $item->unit_price);
    }

    public function taxAmount(): float
    {
        $subtotal = $this->subtotal();

        return (float) $this->taxes->sum(fn (InvoiceTax $tax) => $tax->amount($subtotal));
    }

    /**
     * Total con impuestos incluidos — lo que el cliente realmente debe.
     */
    public function totalAmount(): float
    {
        return $this->subtotal() + $this->taxAmount();
    }

    public function paidAmount(): float
    {
        return (float) $this->payments()->sum('amount');
    }

    public function status(): string
    {
        $paid = $this->paidAmount();
        $total = $this->totalAmount();

        return match (true) {
            $paid <= 0 => 'pendiente',
            $paid < $total => 'parcial',
            default => 'pagada',
        };
    }
}
