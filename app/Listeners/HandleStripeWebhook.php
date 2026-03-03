<?php

namespace App\Listeners;

use App\Models\Tenant;
use App\Services\TenantMailer;
use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;

class HandleStripeWebhook
{
    public function handle(WebhookReceived $event): void
    {
        $payload    = $event->payload;
        $type       = $payload['type'] ?? null;
        $obj        = $payload['data']['object'] ?? [];
        $customerId = $obj['customer'] ?? null;

        if (!$customerId) {
            Log::debug('Stripe webhook: no customer ID in payload', ['type' => $type]);
            return;
        }

        $tenant = Tenant::where('stripe_id', $customerId)->first();

        if (!$tenant) {
            Log::warning('Stripe webhook: tenant not found for customer', ['customer_id' => $customerId, 'event' => $type]);
            return;
        }

        match ($type) {
            'customer.subscription.created',
            'customer.subscription.updated' => $this->handleSubscriptionChange($tenant, $obj),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($tenant, $obj),
            'invoice.payment_succeeded'     => $this->handlePaymentSucceeded($tenant, $obj),
            'invoice.payment_failed'        => $this->handlePaymentFailed($tenant, $obj),
            default => Log::debug('Stripe webhook: unhandled event type', ['type' => $type, 'tenant_id' => $tenant->id]),
        };
    }

    private function handleSubscriptionChange(Tenant $tenant, array $obj): void
    {
        $status  = $obj['status'] ?? null;
        $priceId = $obj['items']['data'][0]['price']['id'] ?? null;

        $sys  = \App\Models\SystemSetting::get();
        $plan = match ($priceId) {
            $sys->stripe_starter_price_id => 'starter',
            $sys->stripe_pro_price_id     => 'pro',
            default                       => $tenant->plan,
        };

        $tenant->update([
            'plan'                       => $plan,
            'stripe_id'                  => $obj['customer'] ?? $tenant->stripe_id,
            'stripe_subscription_id'     => $obj['id'] ?? $tenant->stripe_subscription_id,
            'stripe_subscription_status' => $status,
            // active/trialing = fully active; past_due = grace period (dunning handles suspension)
            'is_active'                  => in_array($status, ['active', 'trialing', 'past_due']),
            // Clear payment_failed_at if subscription becomes active again
            'payment_failed_at'          => in_array($status, ['active', 'trialing']) ? null : $tenant->payment_failed_at,
        ]);

        Log::info('Stripe subscription updated', [
            'tenant_id' => $tenant->id,
            'plan'      => $plan,
            'status'    => $status,
        ]);
    }

    private function handleSubscriptionDeleted(Tenant $tenant, array $obj): void
    {
        $tenant->update([
            'plan'                       => 'trial',
            'stripe_subscription_status' => 'canceled',
            'is_active'                  => false,
            'payment_failed_at'          => null,
        ]);

        Log::info('Stripe subscription cancelled', ['tenant_id' => $tenant->id]);
    }

    private function handlePaymentSucceeded(Tenant $tenant, array $obj): void
    {
        // Skip zero-amount invoices (e.g. free trial activation)
        if (($obj['amount_paid'] ?? 0) === 0) {
            return;
        }

        // Clear any outstanding payment failure state
        $tenant->update([
            'payment_failed_at'          => null,
            'stripe_subscription_status' => 'active',
            'is_active'                  => true,
        ]);

        $amount   = '$' . number_format(($obj['amount_paid'] ?? 0) / 100, 2);
        $planName = ucfirst($tenant->plan) . ' Plan';
        $date     = date('F j, Y', $obj['created'] ?? time());

        $subject = "Payment received — {$amount}";
        $body    = "Hi {$tenant->name},\n\n"
                 . "We've received your payment of {$amount} for your BusyRealtor {$planName}.\n\n"
                 . "Date: {$date}\n"
                 . "Amount: {$amount}\n"
                 . "Plan: {$planName}\n\n"
                 . "You can view your full billing history at any time from your account dashboard.\n\n"
                 . "Thank you for using BusyRealtor!\n\n"
                 . "The BusyRealtor Team";

        TenantMailer::send($tenant->id, $tenant->email, $subject, $body, 'platform');

        Log::info('Billing receipt sent', ['tenant_id' => $tenant->id, 'amount' => $amount]);
    }

    private function handlePaymentFailed(Tenant $tenant, array $obj): void
    {
        // Only record the first failure time; dunning command handles escalation
        $tenant->update([
            'stripe_subscription_status' => 'past_due',
            'payment_failed_at'          => $tenant->payment_failed_at ?? now(),
        ]);

        $amount    = '$' . number_format(($obj['amount_due'] ?? 0) / 100, 2);
        $billingUrl = url("/{$tenant->slug}/admin/billing");

        $subject = 'Action required: Payment failed';
        $body    = "Hi {$tenant->name},\n\n"
                 . "We were unable to process your payment of {$amount} for your BusyRealtor subscription.\n\n"
                 . "Please update your payment method to keep your account active:\n"
                 . "{$billingUrl}\n\n"
                 . "We'll retry automatically, but you can also update your card now to avoid any interruption.\n\n"
                 . "The BusyRealtor Team";

        TenantMailer::send($tenant->id, $tenant->email, $subject, $body, 'platform');

        Log::warning('Stripe payment failed', [
            'tenant_id'  => $tenant->id,
            'invoice_id' => $obj['id'] ?? null,
            'amount'     => $amount,
        ]);
    }
}
