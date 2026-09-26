<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleImpersonation
{
    /**
     * Keep an impersonation session pinned to the tenant it was started for.
     *
     * This used to rebind app('tenant') to the impersonated tenant outright, overriding the
     * slug ResolveTenant had just resolved. A super admin who opened another tenant's admin
     * URL — a bookmark, a link from the tenant list, hand-editing the address — got a page
     * addressed to one tenant and filled with another's data, and a form on that page wrote
     * to the tenant in the session, not the one named in the URL and in every heading on
     * screen. Deleting a listing from the wrong agency was one wrong click away.
     *
     * Super admins can reach any tenant's admin area regardless (see EnsureTenantAdmin), so
     * nothing here grants or withholds access. It only refuses to serve a page whose URL and
     * contents would disagree: stop impersonating, then start again on the tenant you want.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $impersonating = session('impersonating_tenant_id');

        if ($impersonating && app()->bound('tenant') && (int) app('tenant')->id !== (int) $impersonating) {
            abort(403, 'This session is impersonating a different account. Stop impersonating before opening another account.');
        }

        return $next($request);
    }
}
