<?php

use App\Models\Tenant;
use Laravel\Cashier\Events\WebhookReceived;

function makeWebhookTenant(string $id, string $status, string $stripeId): Tenant
{
    return Tenant::withoutEvents(fn () => Tenant::create([
        'id' => $id,
        'name' => 'Webhook Tenant',
        'email' => $id.'@example.com',
        'status' => $status,
        'plan' => 'pro',
        'stripe_id' => $stripeId,
    ]));
}

it('marks a tenant as past due when an invoice payment fails', function () {
    $tenant = makeWebhookTenant('webhookpastdue', 'active', 'cus_pastdue');

    event(new WebhookReceived([
        'type' => 'invoice.payment_failed',
        'data' => ['object' => ['customer' => 'cus_pastdue']],
    ]));

    expect($tenant->fresh()->status)->toBe('past_due');
});

it('suspends a tenant when its subscription is deleted', function () {
    $tenant = makeWebhookTenant('webhookdeleted', 'active', 'cus_deleted');

    event(new WebhookReceived([
        'type' => 'customer.subscription.deleted',
        'data' => ['object' => ['customer' => 'cus_deleted']],
    ]));

    expect($tenant->fresh()->status)->toBe('suspended');
});

it('reactivates a tenant when its subscription becomes active again', function () {
    $tenant = makeWebhookTenant('webhookrecovered', 'past_due', 'cus_recovered');

    event(new WebhookReceived([
        'type' => 'customer.subscription.updated',
        'data' => ['object' => ['customer' => 'cus_recovered', 'status' => 'active']],
    ]));

    expect($tenant->fresh()->status)->toBe('active');
});

it('ignores webhooks for tenants still pending initial payment', function () {
    $tenant = makeWebhookTenant('webhookpending', 'pending', 'cus_pending');

    event(new WebhookReceived([
        'type' => 'invoice.payment_failed',
        'data' => ['object' => ['customer' => 'cus_pending']],
    ]));

    expect($tenant->fresh()->status)->toBe('pending');
});

it('ignores unrelated webhook types', function () {
    $tenant = makeWebhookTenant('webhookunrelated', 'active', 'cus_unrelated');

    event(new WebhookReceived([
        'type' => 'customer.updated',
        'data' => ['object' => ['customer' => 'cus_unrelated']],
    ]));

    expect($tenant->fresh()->status)->toBe('active');
});
