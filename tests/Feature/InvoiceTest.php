<?php

use App\Models\Client;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Database\QueryException;
use Livewire\Volt\Volt;

beforeEach(function () {
    (new PlanSeeder)->run();

    foreach (['pro', 'enterprise'] as $slug) {
        Plan::where('slug', $slug)->first()->items()->create(['name' => 'invoices', 'quantity' => null]);
    }
});

afterEach(function () {
    if (tenancy()->initialized) {
        tenancy()->end();
    }
});

function makeInvoiceTestTenant(string $id, string $plan): Tenant
{
    $tenant = Tenant::create([
        'id' => $id,
        'name' => "Tenant {$id}",
        'email' => "{$id}@example.com",
        'status' => 'active',
        'plan' => $plan,
    ]);
    $tenant->domains()->create(['domain' => $id]);

    return $tenant;
}

it('calculates the invoice total from its line items', function () {
    $tenant = makeInvoiceTestTenant('invoicetotal', 'pro');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    $client = Client::create(['name' => 'Cliente Uno']);
    $category = ProductCategory::create(['name' => 'Materiales']);
    $product = Product::create([
        'product_category_id' => $category->id,
        'name' => 'Concreto',
        'unit_price' => 50,
        'unit' => 'm3',
    ]);

    $invoice = Invoice::create(['client_id' => $client->id]);
    $invoice->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 50, 'quantity' => 3]);
    $invoice->items()->create(['product_id' => $product->id, 'product_name' => $product->name, 'unit_price' => 20, 'quantity' => 2]);

    expect($invoice->totalAmount())->toBe(190.0)
        ->and($invoice->status())->toBe('pendiente');

    $tenant->delete();
});

it('moves an invoice through pendiente, parcial and pagada as payments come in', function () {
    $tenant = makeInvoiceTestTenant('invoicestatus', 'pro');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    $client = Client::create(['name' => 'Cliente Dos']);
    $invoice = Invoice::create(['client_id' => $client->id]);
    $invoice->items()->create(['product_name' => 'Mano de obra', 'unit_price' => 100, 'quantity' => 1]);

    expect($invoice->status())->toBe('pendiente');

    $invoice->payments()->create(['amount' => 40, 'payment_method' => 'efectivo', 'paid_at' => now()]);
    expect($invoice->status())->toBe('parcial');

    $invoice->payments()->create(['amount' => 60, 'payment_method' => 'cheque', 'paid_at' => now()]);
    expect($invoice->status())->toBe('pagada');

    $tenant->delete();
});

it('prevents deleting a product category that still has products', function () {
    $tenant = makeInvoiceTestTenant('categoryrestrict', 'pro');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    $category = ProductCategory::create(['name' => 'Materiales']);
    Product::create(['product_category_id' => $category->id, 'name' => 'Varilla', 'unit_price' => 10]);

    expect(fn () => $category->delete())->toThrow(QueryException::class);

    $tenant->delete();
});

it('creates an invoice with multiple line items through the Volt component', function () {
    $tenant = makeInvoiceTestTenant('invoicecreate', 'pro');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    $client = Client::create(['name' => 'Cliente Tres']);
    $category = ProductCategory::create(['name' => 'Materiales']);
    $product = Product::create(['product_category_id' => $category->id, 'name' => 'Ladrillo', 'unit_price' => 5]);

    Volt::test('tenant.invoices')
        ->call('openCreate')
        ->set('client_id', (string) $client->id)
        ->set('lineItems.0.product_id', $product->id)
        ->set('lineItems.0.quantity', '4')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSet('showModal', false);

    $invoice = Invoice::with('items')->first();

    expect($invoice)->not->toBeNull()
        ->and($invoice->items)->toHaveCount(1)
        ->and($invoice->totalAmount())->toBe(20.0);

    $tenant->delete();
});

it('does not allow a payment larger than the remaining balance', function () {
    $tenant = makeInvoiceTestTenant('invoicepayment', 'pro');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    $client = Client::create(['name' => 'Cliente Cuatro']);
    $invoice = Invoice::create(['client_id' => $client->id]);
    $invoice->items()->create(['product_name' => 'Fee', 'unit_price' => 100, 'quantity' => 1]);

    Volt::test('tenant.invoices')
        ->call('openPayment', $invoice->id)
        ->set('payment_amount', '150')
        ->call('savePayment')
        ->assertHasErrors('payment_amount');

    expect($invoice->fresh()->paidAmount())->toBe(0.0);

    $tenant->delete();
});

it('blocks a basic tenant from accessing invoicing pages', function () {
    $tenant = makeInvoiceTestTenant('basicinvoices', 'basic');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    Volt::test('tenant.invoices')->assertStatus(403);
    Volt::test('tenant.products')->assertStatus(403);
    Volt::test('tenant.product-categories')->assertStatus(403);

    $tenant->delete();
});

it('lets a pro tenant access invoicing pages', function () {
    $tenant = makeInvoiceTestTenant('proinvoices', 'pro');
    tenancy()->initialize($tenant);
    $this->actingAs(User::factory()->create());

    Volt::test('tenant.invoices')->assertOk();
    Volt::test('tenant.products')->assertOk();
    Volt::test('tenant.product-categories')->assertOk();

    $tenant->delete();
});
