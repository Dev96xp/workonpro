<?php

namespace App\Services;

use App\Models\Plan;
use Laravel\Cashier\Cashier;
use Stripe\Exception\ApiErrorException;
use Throwable;

/**
 * Keeps each paid plan's Stripe price in line with the price set in the admin. Stripe prices
 * are immutable, so a new amount means a new recurring price under the same product; existing
 * subscribers keep paying the price they signed up with.
 */
class StripePlanPricing
{
    /**
     * Returns the Stripe price id that charges the plan's current amount, creating it when the
     * one in use charges something else. Free plans never reach Stripe.
     *
     * @throws ApiErrorException
     */
    public function sync(Plan $plan): ?string
    {
        if ($plan->isFree()) {
            return $plan->stripe_price_id;
        }

        $stripe = Cashier::stripe();
        $currentPrice = $this->retrieve($plan->checkoutPriceId());

        if ($currentPrice
            && $currentPrice->unit_amount === $plan->priceInCents()
            && $currentPrice->recurring?->interval === 'month'
            && $currentPrice->active) {
            return $currentPrice->id;
        }

        $productId = $currentPrice?->product
            ?? $stripe->products->create(['name' => 'Workon '.$plan->name, 'metadata' => ['plan' => $plan->slug]])->id;

        return $stripe->prices->create([
            'product' => is_string($productId) ? $productId : $productId->id,
            'unit_amount' => $plan->priceInCents(),
            'currency' => 'usd',
            'recurring' => ['interval' => 'month'],
            'nickname' => $plan->name.' '.$plan->displayPrice(),
            'metadata' => ['plan' => $plan->slug],
        ])->id;
    }

    private function retrieve(?string $priceId): ?object
    {
        if (! $priceId) {
            return null;
        }

        try {
            return Cashier::stripe()->prices->retrieve($priceId);
        } catch (Throwable) {
            return null;
        }
    }
}
