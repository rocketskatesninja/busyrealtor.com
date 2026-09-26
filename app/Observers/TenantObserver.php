<?php

namespace App\Observers;

use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class TenantObserver
{
    /**
     * Run before a Tenant is deleted.
     *
     * Stripe-side cleanup happens FIRST so that even if local cleanup
     * fails halfway through, we've already cancelled their subscription
     * and revoked Customer Portal access. We catch and log any Stripe
     * error so an outage / missing key doesn't block a super-admin
     * from deleting the tenant locally.
     */
    public function deleting(Tenant $tenant): void
    {
        $this->cleanupStripe($tenant);
        $this->cleanupLocalUsers($tenant);
        $this->cleanupCashierRows($tenant);
        $this->cleanupUploads($tenant);
    }

    private function cleanupStripe(Tenant $tenant): void
    {
        if (! $tenant->hasStripeId()) {
            return;
        }

        try {
            $tenant->deleteStripeCustomer();
            Log::info('Stripe customer deleted on tenant removal', [
                'tenant_id' => $tenant->id,
                'stripe_id' => $tenant->stripe_id,
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to delete Stripe customer on tenant removal', [
                'tenant_id' => $tenant->id,
                'stripe_id' => $tenant->stripe_id,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    /**
     * Remove users that belong only to this tenant. The FK is set null,
     * not cascade, so we have to do this by hand. Super admins are
     * spared — they can outlive the tenants they manage.
     */
    private function cleanupLocalUsers(Tenant $tenant): void
    {
        DB::table('users')
            ->where('tenant_id', $tenant->id)
            ->where('is_super_admin', false)
            ->delete();
    }

    /**
     * Cashier's subscriptions table has no FK cascade, so delete child
     * subscription_items first, then the subscriptions themselves.
     */
    /**
     * Remove the tenant's uploaded files.
     *
     * Every child table cascades and the observer handles the rest, but nothing touched
     * storage/app/public/tenants/{id} — so each deleted tenant left its photos behind,
     * unreferenced and invisible, for ever. It has to happen here rather than in a cleanup
     * script because the files are owned by the web user.
     */
    private function cleanupUploads(Tenant $tenant): void
    {
        $directory = 'tenants/'.$tenant->id;

        try {
            if (Storage::disk('public')->exists($directory)) {
                Storage::disk('public')->deleteDirectory($directory);
            }
        } catch (\Throwable $e) {
            // A tenant must still delete if the disk refuses; the row going is the point.
            Log::warning('Could not remove tenant uploads on deletion', [
                'tenant_id' => $tenant->id,
                'directory' => $directory,
                'error'     => $e->getMessage(),
            ]);
        }
    }

    private function cleanupCashierRows(Tenant $tenant): void
    {
        $subIds = DB::table('subscriptions')
            ->where('tenant_id', $tenant->id)
            ->pluck('id');

        if ($subIds->isEmpty()) {
            return;
        }

        DB::table('subscription_items')
            ->whereIn('subscription_id', $subIds)
            ->delete();

        DB::table('subscriptions')
            ->where('tenant_id', $tenant->id)
            ->delete();
    }
}
