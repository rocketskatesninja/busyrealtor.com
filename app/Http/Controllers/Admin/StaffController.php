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
        $staff = StaffMember::where('tenant_id', $tenant->id)->orderBy('sort_order')->get();
        return view('tenant.admin.staff.index', compact('tenant', 'staff'));
    }

    public function store($account, Request $request)
    {
        $request->validate([
            'name'          => 'required|string|max:255',
            'role'          => 'nullable|string|max:255',
            'email'         => 'nullable|email|max:255',
            'phone'         => 'nullable|string|max:30',
            'bio'           => 'nullable|string|max:2000',
            'profile_image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        $tenant = app('tenant');
        $data   = $request->only(['name', 'role', 'email', 'phone', 'bio', 'status']);
        $data['tenant_id']            = $tenant->id;
        $data['display_on_homepage']  = $request->boolean('display_on_homepage');
        $data['accepts_appointments'] = $request->boolean('accepts_appointments');
        $data['sort_order']           = StaffMember::where('tenant_id', $tenant->id)->max('sort_order') + 1;

        if ($request->hasFile('profile_image')) {
            $data['photo_url'] = $this->uploadPhoto($request->file('profile_image'), $tenant->id);
        }

        $member = StaffMember::create($data);
        logActivity('created', "Added staff member: {$member->name}", $member);
        return redirect()->route('tenant.admin.staff.index', ['account' => $tenant->slug])
            ->with('success', 'Staff member added.');
    }

    public function update($account, Request $request, $id)
    {
        $tenant = app('tenant');
        $member = StaffMember::where('tenant_id', $tenant->id)->findOrFail($id);

        $request->validate([
            'name'          => 'required|string|max:255',
            'role'          => 'nullable|string|max:255',
            'email'         => 'nullable|email|max:255',
            'phone'         => 'nullable|string|max:30',
            'bio'           => 'nullable|string|max:2000',
            'profile_image' => 'nullable|image|mimes:jpeg,jpg,png,gif,webp|max:5120',
        ]);

        $data = $request->only(['name', 'role', 'email', 'phone', 'bio', 'status']);
        $data['display_on_homepage']  = $request->boolean('display_on_homepage');
        $data['accepts_appointments'] = $request->boolean('accepts_appointments');

        if ($request->hasFile('profile_image')) {
            if ($member->photo_url) Storage::disk('public')->delete($member->photo_url);
            $data['photo_url'] = $this->uploadPhoto($request->file('profile_image'), $tenant->id);
        }

        $member->update($data);
        logActivity('updated', "Updated staff member: {$member->name}", $member);
        return redirect()->route('tenant.admin.staff.index', ['account' => $tenant->slug])
            ->with('success', 'Staff member updated.');
    }

    public function destroy($account, $id)
    {
        $tenant = app('tenant');
        $member = StaffMember::where('tenant_id', $tenant->id)->findOrFail($id);
        logActivity('deleted', "Removed staff member: {$member->name}", $member);
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
