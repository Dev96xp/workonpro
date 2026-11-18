<?php

namespace App\Services;

use App\Models\Plan;
use App\Models\Tenant;
use Laravel\Cashier\Cashier;
use Stripe\Subscription;

/**
 * Lets a tenant move to another paid plan by itself. It talks to Stripe directly instead of
 * the local `subscriptions` table, because that table is only filled by webhooks and may be
 * empty (local environment) or stale.
 */
class TenantPlanChanger
{
    private const LIVE_STATUSES = ['active', 'trialing', 'past_due'];

    /**
     * Whether the tenant pays through a Stripe subscription that can be swapped in place.
     */
    public function hasSubscription(Tenant $tenant): bool
    {
        return $this->activeSubscription($tenant) !== null;
    }

    /**
     * Moves the tenant's current Stripe subscription to the plan's price. Stripe prorates the
     * difference on the next invoice.
     */
    public function swap(Tenant $tenant, Plan $plan): void
    {
        $subscription = $this->activeSubscription($tenant);

        if ($subscription === null) {
            throw new \RuntimeException("Tenant {$tenant->id} has no active Stripe subscription.");
        }

        Cashier::stripe()->subscriptions->update($subscription->id, [
            'items' => [['id' => $subscription->items->data[0]->id, 'price' => $plan->checkoutPriceId()]],
            'proration_behavior' => 'create_prorations',
            'metadata' => ['plan' => $plan->slug],
        ]);

        $tenant->update(['plan' => $plan->slug]);
    }

    /**
     * Stripe Checkout page for a tenant that has no subscription yet (for example one that
     * signed up on a free promotion).
     */
    public function checkoutUrl(Tenant $tenant, Plan $plan, string $returnUrl): string
    {
        return $tenant
            ->newSubscription('default', $plan->checkoutPriceId())
            ->withMetadata(['plan' => $plan->slug])
            ->checkout([
                'success_url' => $returnUrl.'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => $returnUrl,
            ])
            ->url;
    }

    /**
     * Applies the plan paid for in a finished Checkout session and returns its slug, or null
     * when the session is not this tenant's or was not paid.
     */
    public function confirmCheckout(Tenant $tenant, string $sessionId): ?string
    {
        $session = Cashier::stripe()->checkout->sessions->retrieve($sessionId, ['expand' => ['subscription']]);

        $slug = $session->subscription?->metadata['plan'] ?? null;

        if ($session->customer !== $tenant->stripe_id
            || $session->payment_status !== 'paid'
            || ! Plan::query()->where('slug', $slug)->exists()) {
            return null;
        }

        $tenant->update(['plan' => $slug]);

        return $slug;
    }

    public function billingPortalUrl(Tenant $tenant, string $returnUrl): ?string
    {
        return $tenant->stripe_id ? $tenant->billingPortalUrl($returnUrl) : null;
    }

    private function activeSubscription(Tenant $tenant): ?Subscription
    {
        if (! $tenant->stripe_id) {
            return null;
        }

        $subscriptions = Cashier::stripe()->subscriptions->all(['customer' => $tenant->stripe_id, 'status' => 'all', 'limit' => 10]);

        foreach ($subscriptions->data as $subscription) {
            if (in_array($subscription->status, self::LIVE_STATUSES, true)) {
                return $subscription;
            }
        }

        return null;
    }
}
