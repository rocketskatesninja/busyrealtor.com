<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StaffMember;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class StaffController extends Controller
{
    public function index($account)
    {
        $tenant = app('tenant');
        if (!$tenant->isPro()) {
            return redirect()->route('tenant.admin.billing', $account)
                ->with('error', 'Staff management is a Pro plan feature. Upgrade to access it.');
        }
        $staff = StaffMember::orderBy('sort_order')->get();
        return view('tenant.admin.staff.index', compact('tenant', 'staff'));
    }

    public function store($account, Request $request)
    {
        $request->validate([
            'name'  => 'required|string|max:255',
            'role'  => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'bio'   => 'nullable|string|max:2000',
        ]);

        $tenant = app('tenant');
        $data   = $request->only(['name', 'role', 'email', 'phone', 'bio', 'status']);
        $data['tenant_id']            = $tenant->id;
        $data['display_on_homepage']  = $request->boolean('display_on_homepage');
        $data['accepts_appointments'] = $request->boolean('accepts_appointments');
        $data['sort_order']           = StaffMember::max('sort_order') + 1;

        if ($request->hasFile('profile_image')) {
            $data['photo_url'] = $this->uploadPhoto($request->file('profile_image'), $tenant->id);
        }

        StaffMember::create($data);
        return redirect()->route('tenant.admin.staff.index', ['account' => $tenant->slug])
            ->with('success', 'Staff member added.');
    }

    public function update($account, Request $request, $id)
    {
        $tenant = app('tenant');
        $member = StaffMember::where('tenant_id', $tenant->id)->findOrFail($id);

        $request->validate([
            'name'  => 'required|string|max:255',
            'role'  => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:30',
            'bio'   => 'nullable|string|max:2000',
        ]);

        $data = $request->only(['name', 'role', 'email', 'phone', 'bio', 'status']);
        $data['display_on_homepage']  = $request->boolean('display_on_homepage');
        $data['accepts_appointments'] = $request->boolean('accepts_appointments');

        if ($request->hasFile('profile_image')) {
            if ($member->photo_url) Storage::disk('public')->delete($member->photo_url);
            $data['photo_url'] = $this->uploadPhoto($request->file('profile_image'), $tenant->id);
        }

        $member->update($data);
        return redirect()->route('tenant.admin.staff.index', ['account' => $tenant->slug])
            ->with('success', 'Staff member updated.');
    }

    public function destroy($account, $id)
    {
        $tenant = app('tenant');
        $member = StaffMember::where('tenant_id', $tenant->id)->findOrFail($id);
        if ($member->photo_url) Storage::disk('public')->delete($member->photo_url);
        $member->delete();
        return redirect()->route('tenant.admin.staff.index', ['account' => $tenant->slug])
            ->with('success', 'Staff member removed.');
    }

    private function uploadPhoto($file, int $tenantId): string
    {
        $dir  = "tenants/{$tenantId}/staff";
        Storage::disk('public')->makeDirectory($dir);
        $path = $dir . '/' . uniqid() . '.jpg';
        Storage::disk('public')->put($path, Image::read($file)->scale(width: 400)->toJpeg(85));
        return $path;
    }
}
