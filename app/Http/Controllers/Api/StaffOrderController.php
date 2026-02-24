<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StaffMember;
use Illuminate\Http\Request;

class StaffOrderController extends Controller
{
    public function save($account, Request $request)
    {
        $tenant = app('tenant');
        $ids    = array_map('intval', $request->order ?? []);
        if (empty($ids)) return response()->json(['success' => true]);

        // Only update staff members that belong to this tenant
        foreach ($ids as $i => $id) {
            StaffMember::where('id', $id)->where('tenant_id', $tenant->id)->update(['sort_order' => $i]);
        }
        return response()->json(['success' => true]);
    }
}
