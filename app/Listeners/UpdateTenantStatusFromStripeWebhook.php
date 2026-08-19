<?php

namespace App\Listeners;

use App\Models\Tenant;
use Laravel\Cashier\Events\WebhookReceived;

class UpdateTenantStatusFromStripeWebhook
{
    private const RELEVANT_TYPES = [
        'customer.subscription.updated',
        'customer.subscription.deleted',
        'invoice.payment_failed',
    ];

    public function handle(WebhookReceived $event): void
    {
        $type = $event->payload['type'] ?? null;

        if (! in_array($type, self::RELEVANT_TYPES, true)) {
            return;
        }

        $object = $event->payload['data']['object'] ?? [];
        $customerId = $object['customer'] ?? null;

        if ($customerId === null) {
            return;
        }

        $tenant = Tenant::where('stripe_id', $customerId)->first();

        // El tenant sigue en checkout inicial; register.success.blade.php es quien lo activa.
        if ($tenant === null || $tenant->status === 'pending') {
            return;
        }

        $status = $this->resolveStatus($type, $object);

        if ($status !== null && $tenant->status !== $status) {
            $tenant->update(['status' => $status]);
        }
    }

    private function resolveStatus(string $type, array $object): ?string
    {
        return match ($type) {
            'invoice.payment_failed' => 'past_due',
            'customer.subscription.deleted' => 'suspended',
            'customer.subscription.updated' => match ($object['status'] ?? null) {
                'active', 'trialing' => 'active',
                'canceled', 'unpaid' => 'suspended',
                'past_due' => 'past_due',
                default => null,
            },
            default => null,
        };
    }
}
