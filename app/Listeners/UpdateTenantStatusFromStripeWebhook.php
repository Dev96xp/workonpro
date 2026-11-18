<?php

namespace App\Listeners;

use App\Models\Plan;
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
        $plan = $type === 'customer.subscription.updated' ? $this->resolvePlan($object) : null;

        $changes = array_filter([
            'status' => $status !== null && $tenant->status !== $status ? $status : null,
            'plan' => $plan !== null && $tenant->plan !== $plan ? $plan : null,
        ]);

        if ($changes !== []) {
            $tenant->update($changes);
        }
    }

    /**
     * Plan the subscription now charges for, so a plan changed from the tenant panel, the Stripe
     * portal or the Stripe dashboard always ends up the same in Workon.
     */
    private function resolvePlan(array $object): ?string
    {
        $slug = $object['metadata']['plan'] ?? null;

        if ($slug !== null && Plan::query()->where('slug', $slug)->exists()) {
            return $slug;
        }

        $priceId = $object['items']['data'][0]['price']['id'] ?? null;

        if ($priceId === null) {
            return null;
        }

        return Plan::query()->where('stripe_price_id', $priceId)->value('slug')
            ?: (array_search($priceId, array_filter((array) config('services.stripe.prices')), true) ?: null);
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
