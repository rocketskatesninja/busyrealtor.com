<?php

namespace App\Services;

use App\Models\Tenant;
use Laravel\Cashier\Cashier;
use Throwable;

/**
 * Does this tenant actually have a live subscription?
 *
 * Exists because `plan` is only ever set to 'pro' by the Stripe webhook. A webhook that is
 * delayed, dropped, or rejected for a signature mismatch — the single most common thing to
 * get wrong when moving from test keys to live ones — leaves a paying customer looking
 * exactly like a lapsed trial to anything reading the column.
 *
 * So before an account is shut off, this asks Stripe rather than trusting local state.
 */
class BillingStatus
{
    /**
     * @return bool|null true if subscribed, false if definitely not, null if we could not tell
     *
     * The null matters more than the other two. An inconclusive answer must never be read as
     * "no subscription" — that is precisely the case where shutting the account off is both
     * wrong and the hardest kind of wrong to notice.
     */
    public function hasLiveSubscription(Tenant $tenant): ?bool
    {
        // Cheap local answer first; Cashier keeps this in step with the same webhooks, but
        // when it says yes it is saying a subscription row exists, which is enough.
        if ($tenant->subscribed()) {
            return true;
        }

        // Never reached checkout, so there is nothing at Stripe to ask about.
        if (! $tenant->hasStripeId()) {
            return false;
        }

        try {
            $subscriptions = Cashier::stripe()->subscriptions->all([
                'customer' => $tenant->stripe_id,
                'status' => 'all',
                'limit' => 20,
            ]);
        } catch (Throwable $e) {
            report($e);

            return null;
        }

        foreach ($subscriptions->data as $subscription) {
            // past_due is deliberately counted as live: the card failed, Stripe is retrying,
            // and cutting the site off mid-dunning turns a recoverable payment into a
            // cancelled customer.
            if (in_array($subscription->status, ['active', 'trialing', 'past_due'], true)) {
                return true;
            }
        }

        return false;
    }
}
