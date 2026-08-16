<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceTax;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Tax;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use Livewire\WithPagination;

new #[Layout('components.layouts.tenant')] class extends Component {
    use WithPagination;

    public string $search = '';

    // Modal: crear/editar factura
    public bool $showModal = false;
    public ?int $editingId = null;

    #[Validate('required|exists:clients,id')]
    public string $client_id = '';

    #[Validate('nullable|string')]
    public string $description = '';

    #[Validate('nullable|string')]
    public string $notes = '';

    /** @var array<int, array{product_id: ?int, product_name: string, unit_price: string, quantity: string}> */
    public array $lineItems = [];

    /** @var array<int, array{tax_id: ?int, name: string, rate: string}> */
    public array $taxItems = [];

    // Modal: confirmar borrado de factura
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    // Modal: agregar pago
    public bool $showPaymentModal = false;
    public ?int $payingInvoiceId = null;

    // Reglas de estos campos se validan explícitamente en savePayment() para no
    // interferir con el $this->validate() de save() (factura).
    public string $payment_amount = '';

    public string $payment_method = 'efectivo';

    public string $payment_paid_at = '';

    public string $payment_notes = '';

    public function mount(): void
    {
        abort_unless(Tenant::hasFeature(tenant('plan'), 'invoices'), 403);
    }

    public function with(): array
    {
        return [
            'invoices' => Invoice::query()
                ->with(['client', 'items', 'payments'])
                ->when($this->search, fn ($q) => $q->whereHas('client', fn ($c) => $c->where('name', 'like', "%{$this->search}%")))
                ->latest()
                ->paginate(10),
            'clientOptions' => Client::orderBy('name')->get(),
            'productOptions' => Product::where('is_active', true)->with('category')->orderBy('name')->get(),
            'taxOptions' => Tax::where('is_active', true)->orderBy('name')->get(),
            'editingPayments' => $this->editingId
                ? Payment::where('invoice_id', $this->editingId)->latest('paid_at')->get()
                : collect(),
        ];
    }

    public function addLineItem(): void
    {
        $this->lineItems[] = ['product_id' => null, 'product_name' => '', 'unit_price' => '0', 'quantity' => '1'];
    }

    public function removeLineItem(int $index): void
    {
        unset($this->lineItems[$index]);
        $this->lineItems = array_values($this->lineItems);
    }

    public function updatedLineItems($value, $key): void
    {
        [$index, $field] = explode('.', $key);

        if ($field === 'product_id' && $value) {
            $product = Product::find($value);
            if ($product) {
                $this->lineItems[$index]['product_name'] = $product->name;
                $this->lineItems[$index]['unit_price'] = (string) $product->unit_price;
            }
        }
    }

    public function lineItemSubtotal(int $index): float
    {
        $item = $this->lineItems[$index] ?? null;

        if (! $item) {
            return 0.0;
        }

        return (float) ($item['quantity'] ?: 0) * (float) ($item['unit_price'] ?: 0);
    }

    public function invoiceTotal(): float
    {
        return collect($this->lineItems)->sum(
            fn (array $item) => (float) ($item['quantity'] ?: 0) * (float) ($item['unit_price'] ?: 0)
        );
    }

    public function addTaxItem(): void
    {
        $this->taxItems[] = ['tax_id' => null, 'name' => '', 'rate' => '0'];
    }

    public function removeTaxItem(int $index): void
    {
        unset($this->taxItems[$index]);
        $this->taxItems = array_values($this->taxItems);
    }

    public function updatedTaxItems($value, $key): void
    {
        [$index, $field] = explode('.', $key);

        if ($field === 'tax_id' && $value) {
            $tax = Tax::find($value);
            if ($tax) {
                $this->taxItems[$index]['name'] = $tax->name;
                $this->taxItems[$index]['rate'] = (string) $tax->rate;
            }
        }
    }

    public function taxItemAmount(int $index): float
    {
        $item = $this->taxItems[$index] ?? null;

        if (! $item) {
            return 0.0;
        }

        return round($this->invoiceTotal() * ((float) ($item['rate'] ?: 0) / 100), 2);
    }

    public function taxTotal(): float
    {
        return collect($this->taxItems)->sum(
            fn (array $item) => round($this->invoiceTotal() * ((float) ($item['rate'] ?: 0) / 100), 2)
        );
    }

    public function grandTotal(): float
    {
        return $this->invoiceTotal() + $this->taxTotal();
    }

    public function openCreate(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->addLineItem();
        $this->showModal = true;
    }

    public function openEdit(int $id): void
    {
        $invoice = Invoice::with('items', 'taxes')->findOrFail($id);
        $this->editingId = $id;
        $this->client_id = (string) $invoice->client_id;
        $this->description = $invoice->description ?? '';
        $this->notes = $invoice->notes ?? '';
        $this->lineItems = $invoice->items->map(fn (InvoiceItem $item) => [
            'product_id' => $item->product_id,
            'product_name' => $item->product_name,
            'unit_price' => (string) $item->unit_price,
            'quantity' => (string) $item->quantity,
        ])->all();
        $this->taxItems = $invoice->taxes->map(fn (InvoiceTax $tax) => [
            'tax_id' => $tax->tax_id,
            'name' => $tax->name,
            'rate' => (string) $tax->rate,
        ])->all();

        if (empty($this->lineItems)) {
            $this->addLineItem();
        }

        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate([
            'client_id' => 'required|exists:clients,id',
            'description' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $this->lineItems = array_values($this->lineItems);

        if (empty($this->lineItems)) {
            $this->addError('lineItems', __('tenant.invoices.items_required'));

            return;
        }

        foreach ($this->lineItems as $item) {
            if (empty($item['product_id']) || (float) ($item['quantity'] ?: 0) <= 0) {
                $this->addError('lineItems', __('tenant.invoices.items_invalid'));

                return;
            }
        }

        DB::transaction(function () {
            $invoice = $this->editingId
                ? tap(Invoice::findOrFail($this->editingId))->update([
                    'client_id' => $this->client_id,
                    'description' => $this->description ?: null,
                    'notes' => $this->notes ?: null,
                ])
                : Invoice::create([
                    'client_id' => $this->client_id,
                    'description' => $this->description ?: null,
                    'notes' => $this->notes ?: null,
                ]);

            $invoice->items()->delete();

            foreach ($this->lineItems as $item) {
                $invoice->items()->create([
                    'product_id' => $item['product_id'],
                    'product_name' => $item['product_name'],
                    'unit_price' => $item['unit_price'],
                    'quantity' => $item['quantity'],
                ]);
            }

            $invoice->taxes()->delete();

            foreach ($this->taxItems as $tax) {
                if (empty($tax['tax_id'])) {
                    continue;
                }

                $invoice->taxes()->create([
                    'tax_id' => $tax['tax_id'],
                    'name' => $tax['name'],
                    'rate' => $tax['rate'],
                ]);
            }
        });

        $this->showModal = false;
        $this->resetForm();
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(): void
    {
        if ($this->deletingId) {
            Invoice::findOrFail($this->deletingId)->delete();
        }

        $this->showDeleteModal = false;
        $this->deletingId = null;
    }

    public function openPayment(int $invoiceId): void
    {
        $this->payingInvoiceId = $invoiceId;
        $this->payment_amount = '';
        $this->payment_method = 'efectivo';
        $this->payment_paid_at = now()->toDateString();
        $this->payment_notes = '';
        $this->resetValidation();
        $this->showPaymentModal = true;
    }

    public function savePayment(): void
    {
        $this->validate([
            'payment_amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|in:efectivo,cheque',
            'payment_paid_at' => 'required|date',
            'payment_notes' => 'nullable|string',
        ]);

        $invoice = Invoice::findOrFail($this->payingInvoiceId);
        $remaining = $invoice->totalAmount() - $invoice->paidAmount();

        if ((float) $this->payment_amount > $remaining) {
            $this->addError('payment_amount', __('tenant.invoices.payment_exceeds_balance'));

            return;
        }

        $invoice->payments()->create([
            'amount' => $this->payment_amount,
            'payment_method' => $this->payment_method,
            'paid_at' => $this->payment_paid_at,
            'notes' => $this->payment_notes ?: null,
        ]);

        $this->showPaymentModal = false;
        $this->payingInvoiceId = null;
    }

    public function deletePayment(int $paymentId): void
    {
        Payment::findOrFail($paymentId)->delete();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function logout(): void
    {
        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();
        $this->redirect(url('/'));
    }

    private function resetForm(): void
    {
        $this->client_id = '';
        $this->description = '';
        $this->notes = '';
        $this->lineItems = [];
        $this->taxItems = [];
        $this->resetValidation();
    }
}; ?>

<div class="min-h-screen bg-zinc-50 dark:bg-zinc-900">
    @include('livewire.tenant.partials.navbar')

    <div class="flex">
        @include('livewire.tenant.partials.sidebar')

        <flux:main>
            <div class="p-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <flux:heading size="xl">{{ __('tenant.invoices.heading') }}</flux:heading>
                        <flux:text class="text-zinc-500">{{ __('tenant.invoices.subheading') }}</flux:text>
                    </div>
                    <flux:button wire:click="openCreate" variant="primary" icon="plus" class="sm:w-auto">
                        {{ __('tenant.invoices.new') }}
                    </flux:button>
                </div>

                <div class="mt-6">
                    <flux:input wire:model.live.debounce.300ms="search" placeholder="{{ __('tenant.invoices.search_placeholder') }}" icon="magnifying-glass" />
                </div>

                <div class="mt-4 overflow-x-auto rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-800">
                    <table class="min-w-full divide-y divide-zinc-200 dark:divide-zinc-700">
                        <thead class="bg-zinc-50 dark:bg-zinc-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.common.name') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.invoices.total_label') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.invoices.paid_label') }}</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.common.status') }}</th>
                                <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-zinc-500">{{ __('tenant.common.actions') }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                            @forelse ($invoices as $invoice)
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                                    <td class="px-6 py-4 font-medium">{{ $invoice->client->name }}</td>
                                    <td class="px-6 py-4 text-zinc-500">${{ number_format($invoice->totalAmount(), 2) }}</td>
                                    <td class="px-6 py-4 text-zinc-500">${{ number_format($invoice->paidAmount(), 2) }}</td>
                                    <td class="px-6 py-4">
                                        @if ($invoice->status() === 'pagada')
                                            <flux:badge color="green" size="sm">{{ __('tenant.invoices.status_pagada') }}</flux:badge>
                                        @elseif ($invoice->status() === 'parcial')
                                            <flux:badge color="amber" size="sm">{{ __('tenant.invoices.status_parcial') }}</flux:badge>
                                        @else
                                            <flux:badge color="zinc" size="sm">{{ __('tenant.invoices.status_pendiente') }}</flux:badge>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <flux:button wire:click="openPayment({{ $invoice->id }})" size="sm" icon="banknotes" />
                                            <flux:button wire:click="openEdit({{ $invoice->id }})" size="sm" icon="pencil" />
                                            <flux:button wire:click="confirmDelete({{ $invoice->id }})" size="sm" icon="trash" variant="danger" />
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-12 text-center text-zinc-500">
                                        {{ __('tenant.invoices.empty') }}
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>

                    @if ($invoices->hasPages())
                        <div class="border-t border-zinc-200 px-6 py-3 dark:border-zinc-700">
                            {{ $invoices->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </flux:main>
    </div>

    {{-- Modal Crear/Editar Factura --}}
    <flux:modal wire:model="showModal" class="w-full max-w-2xl">
        <div class="flex items-start justify-between pr-12">
            <flux:heading size="lg">{{ $editingId ? __('tenant.invoices.edit') : __('tenant.invoices.new') }}</flux:heading>
            @if ($editingId)
                <flux:text class="text-zinc-400">{{ __('tenant.invoices.invoice_number', ['id' => $editingId]) }}</flux:text>
            @endif
        </div>

        <form wire:submit="save" class="mt-4 space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('tenant.invoices.client_label') }} <span class="text-red-500">*</span></flux:label>
                    <flux:select wire:model="client_id">
                        <flux:select.option value="">{{ __('tenant.invoices.select_client') }}</flux:select.option>
                        @foreach ($clientOptions as $client)
                            <flux:select.option value="{{ $client->id }}">{{ $client->name }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="client_id" />
                </flux:field>

                <flux:field class="sm:col-span-2">
                    <flux:label>{{ __('tenant.common.description') }}</flux:label>
                    <flux:textarea wire:model="description" rows="2" placeholder="¿Qué se está facturando?..." />
                    <flux:error name="description" />
                </flux:field>
            </div>

            {{-- Líneas de la factura --}}
            <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:text class="mb-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('tenant.invoices.items_label') }}
                </flux:text>

                <div class="space-y-3">
                    @foreach ($lineItems as $i => $item)
                        <div class="grid grid-cols-12 items-end gap-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <div class="col-span-12 sm:col-span-5">
                                <flux:label class="text-xs">{{ __('tenant.invoices.product_label') }}</flux:label>
                                <flux:select wire:model.live="lineItems.{{ $i }}.product_id" size="sm">
                                    <flux:select.option value="">{{ __('tenant.invoices.select_product') }}</flux:select.option>
                                    @foreach ($productOptions as $product)
                                        <flux:select.option value="{{ $product->id }}">{{ $product->name }} ({{ $product->category->name }})</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <div class="col-span-4 sm:col-span-2">
                                <flux:label class="text-xs">{{ __('tenant.invoices.quantity_label') }}</flux:label>
                                <flux:input wire:model.live="lineItems.{{ $i }}.quantity" type="number" step="0.01" min="0" size="sm" />
                            </div>

                            <div class="col-span-4 sm:col-span-2">
                                <flux:label class="text-xs">{{ __('tenant.common.price') }}</flux:label>
                                <flux:input wire:model.live="lineItems.{{ $i }}.unit_price" type="number" step="0.01" min="0" size="sm" />
                            </div>

                            <div class="col-span-3 sm:col-span-2">
                                <flux:label class="text-xs">{{ __('tenant.invoices.subtotal_label') }}</flux:label>
                                <flux:text class="py-1.5 text-sm font-semibold">${{ number_format($this->lineItemSubtotal($i), 2) }}</flux:text>
                            </div>

                            <div class="col-span-1 flex justify-end">
                                <flux:button type="button" wire:click="removeLineItem({{ $i }})" wire:confirm="{{ __('tenant.invoices.confirm_remove_item') }}" size="sm" icon="trash" variant="danger" />
                            </div>
                        </div>
                    @endforeach
                </div>

                <flux:error name="lineItems" />

                <flux:button type="button" wire:click="addLineItem" size="sm" icon="plus" class="mt-3">
                    {{ __('tenant.invoices.add_item') }}
                </flux:button>
            </div>

            {{-- Impuestos --}}
            <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:text class="mb-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                    {{ __('tenant.invoices.taxes_label') }}
                </flux:text>

                <div class="space-y-3">
                    @foreach ($taxItems as $i => $item)
                        <div class="grid grid-cols-12 items-end gap-2 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                            <div class="col-span-12 sm:col-span-6">
                                <flux:label class="text-xs">{{ __('tenant.taxes.heading') }}</flux:label>
                                <flux:select wire:model.live="taxItems.{{ $i }}.tax_id" size="sm">
                                    <flux:select.option value="">{{ __('tenant.invoices.select_tax') }}</flux:select.option>
                                    @foreach ($taxOptions as $tax)
                                        <flux:select.option value="{{ $tax->id }}">{{ $tax->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>

                            <div class="col-span-4 sm:col-span-2">
                                <flux:label class="text-xs">{{ __('tenant.taxes.rate_label') }}</flux:label>
                                <flux:input wire:model.live="taxItems.{{ $i }}.rate" type="number" step="0.01" min="0" max="100" size="sm" />
                            </div>

                            <div class="col-span-4 sm:col-span-3">
                                <flux:label class="text-xs">{{ __('tenant.invoices.subtotal_label') }}</flux:label>
                                <flux:text class="py-1.5 text-sm font-semibold">${{ number_format($this->taxItemAmount($i), 2) }}</flux:text>
                            </div>

                            <div class="col-span-4 sm:col-span-1 flex justify-end">
                                <flux:button type="button" wire:click="removeTaxItem({{ $i }})" size="sm" icon="trash" variant="danger" />
                            </div>
                        </div>
                    @endforeach
                </div>

                <flux:button type="button" wire:click="addTaxItem" size="sm" icon="plus" class="mt-3">
                    {{ __('tenant.invoices.add_tax') }}
                </flux:button>
            </div>

            {{-- Totales --}}
            <div class="flex flex-col items-end gap-1 border-t border-zinc-200 pt-3 dark:border-zinc-700">
                <flux:text class="text-sm text-zinc-500">
                    {{ __('tenant.invoices.subtotal_label') }}: ${{ number_format($this->invoiceTotal(), 2) }}
                </flux:text>
                @if ($this->taxTotal() > 0)
                    <flux:text class="text-sm text-zinc-500">
                        {{ __('tenant.invoices.tax_total_label') }}: ${{ number_format($this->taxTotal(), 2) }}
                    </flux:text>
                @endif
                <flux:text class="text-lg font-bold">
                    {{ __('tenant.invoices.total_label') }}: ${{ number_format($this->grandTotal(), 2) }}
                </flux:text>
                @if ($editingId)
                    <flux:text class="text-sm text-zinc-500">
                        {{ __('tenant.invoices.balance_label') }}: ${{ number_format($this->grandTotal() - $editingPayments->sum('amount'), 2) }}
                    </flux:text>
                @endif
            </div>

            {{-- Pagos registrados --}}
            @if ($editingId)
                <div class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                    <flux:text class="mb-3 text-sm font-medium text-zinc-700 dark:text-zinc-300">
                        {{ __('tenant.invoices.payments_heading') }}
                    </flux:text>

                    <div class="space-y-2">
                        @forelse ($editingPayments as $payment)
                            <div class="flex items-center justify-between rounded-lg border border-zinc-200 px-3 py-2 dark:border-zinc-700">
                                <div>
                                    <p class="text-sm font-semibold">${{ number_format($payment->amount, 2) }}</p>
                                    <p class="text-xs text-zinc-500">
                                        {{ $payment->payment_method === 'efectivo' ? __('tenant.invoices.method_efectivo') : __('tenant.invoices.method_cheque') }}
                                        · {{ $payment->paid_at->format('d/m/Y') }}
                                    </p>
                                </div>
                                <flux:button type="button" wire:click="deletePayment({{ $payment->id }})" wire:confirm="{{ __('tenant.invoices.confirm_delete_payment') }}" size="sm" icon="trash" variant="danger" />
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500">{{ __('tenant.invoices.no_payments') }}</p>
                        @endforelse
                    </div>
                </div>
            @endif

            {{-- Cláusulas de pago / notas al pie de la factura --}}
            <flux:field class="border-t border-zinc-200 pt-4 dark:border-zinc-700">
                <flux:label>{{ __('tenant.invoices.terms_label') }}</flux:label>
                <flux:textarea wire:model="notes" rows="3" placeholder="{{ __('tenant.invoices.terms_placeholder') }}" />
                <flux:error name="notes" />
            </flux:field>

            <div class="flex justify-end gap-3 pt-2">
                @if ($editingId)
                    <flux:button href="{{ url('/invoices/'.$editingId.'/print') }}" target="_blank" icon="printer" class="me-auto">
                        {{ __('tenant.invoices.print') }}
                    </flux:button>
                @endif
                <flux:button type="button" wire:click="$set('showModal', false)">{{ __('tenant.common.cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ $editingId ? __('tenant.common.save_changes') : __('tenant.invoices.new') }}</span>
                    <span wire:loading>{{ __('tenant.common.saving') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal Agregar Pago --}}
    <flux:modal wire:model="showPaymentModal" class="w-full max-w-sm">
        <flux:heading size="lg">{{ __('tenant.invoices.add_payment') }}</flux:heading>

        <form wire:submit="savePayment" class="mt-4 space-y-4">
            <flux:field>
                <flux:label>{{ __('tenant.invoices.amount_label') }} <span class="text-red-500">*</span></flux:label>
                <flux:input wire:model="payment_amount" type="number" step="0.01" min="0" placeholder="0.00" />
                <flux:error name="payment_amount" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('tenant.invoices.payment_method_label') }}</flux:label>
                <flux:select wire:model="payment_method">
                    <flux:select.option value="efectivo">{{ __('tenant.invoices.method_efectivo') }}</flux:select.option>
                    <flux:select.option value="cheque">{{ __('tenant.invoices.method_cheque') }}</flux:select.option>
                </flux:select>
                <flux:error name="payment_method" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('tenant.invoices.paid_at_label') }}</flux:label>
                <flux:input wire:model="payment_paid_at" type="date" />
                <flux:error name="payment_paid_at" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('tenant.common.description') }}</flux:label>
                <flux:textarea wire:model="payment_notes" rows="2" />
                <flux:error name="payment_notes" />
            </flux:field>

            <div class="flex justify-end gap-3 pt-2">
                <flux:button type="button" wire:click="$set('showPaymentModal', false)">{{ __('tenant.common.cancel') }}</flux:button>
                <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                    <span wire:loading.remove>{{ __('tenant.invoices.add_payment') }}</span>
                    <span wire:loading>{{ __('tenant.common.saving') }}</span>
                </flux:button>
            </div>
        </form>
    </flux:modal>

    {{-- Modal Confirmar Eliminar --}}
    <flux:modal wire:model="showDeleteModal" class="max-w-sm">
        <div class="text-center">
            <div class="mx-auto mb-4 flex size-12 items-center justify-center rounded-full bg-red-100 dark:bg-red-900">
                <flux:icon.trash class="size-6 text-red-600 dark:text-red-400" />
            </div>
            <flux:heading size="lg">{{ __('tenant.invoices.confirm_delete') }}</flux:heading>
            <flux:text class="mt-2 text-zinc-500">{{ __('tenant.common.cannot_undo') }}</flux:text>
        </div>

        <div class="mt-6 flex justify-center gap-3">
            <flux:button wire:click="$set('showDeleteModal', false)">{{ __('tenant.common.cancel') }}</flux:button>
            <flux:button wire:click="delete" variant="danger">{{ __('tenant.common.delete') }}</flux:button>
        </div>
    </flux:modal>
</div>
