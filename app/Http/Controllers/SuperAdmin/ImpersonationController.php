<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ImpersonationController extends Controller
{
    public function impersonate(Request $request, Tenant $tenant)
    {
        $request->session()->regenerate();
        $request->session()->put('impersonating_tenant_id', $tenant->id);
        $request->session()->put('super_admin_id', Auth::id());
        return redirect()->route('tenant.admin.dashboard', ['account' => $tenant->slug]);
    }

    public function stop(Request $request)
    {
        $request->session()->forget(['impersonating_tenant_id', 'super_admin_id']);
        $request->session()->regenerate();
        return redirect()->route('super.dashboard');
    }
}
