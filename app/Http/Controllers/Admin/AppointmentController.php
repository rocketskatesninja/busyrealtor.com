<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Property;
use App\Models\SiteSettings;
use App\Services\TenantMailer;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AppointmentController extends Controller
{
    public function index($account, Request $request)
    {
        $tenant = app('tenant');
        if (!$tenant->isPro()) {
            return redirect()->route('tenant.admin.billing', $account)
                ->with('error', 'Appointment management is a Pro plan feature. Upgrade to access it.');
        }
        $query  = Appointment::with('property');

        if ($request->status)     $query->where('status', $request->status);
        if ($request->date_from)  $query->where('appointment_date', '>=', $request->date_from);
        if ($request->date_to)    $query->where('appointment_date', '<=', $request->date_to);

        $appointments = $query->orderBy('appointment_date')->paginate(25)->withQueryString();
        return view('tenant.admin.appointments.index', compact('tenant', 'appointments'));
    }

    public function storePublic($account, Request $request)
    {
        $tenant = app('tenant');
        if (!$tenant->isPro()) {
            return response()->json(['success' => false, 'message' => 'Appointment booking is not available for this agency.'], 403);
        }
        $request->validate([
            'visitor_name'   => 'required|string|max:255',
            'visitor_email'  => 'required|email',
            'visitor_phone'  => 'nullable|string|max:30',
            'appointment_date' => 'required|date',
            'appointment_type' => 'nullable|string',
            'message'        => 'nullable|string|max:2000',
            'property_id'    => 'nullable|integer',
        ]);

        // Resolve assigned staff member from property (Pro only)
        $staffMemberId = null;
        $staffEmail    = null;
        if ($request->property_id) {
            $property = \App\Models\Property::with('staffMember')->find($request->property_id);
            if ($property && $property->staff_member_id) {
                $staffMemberId = $property->staff_member_id;
                if ($tenant->isPro() && $property->staffMember && $property->staffMember->email) {
                    $staffEmail = $property->staffMember->email;
                }
            }
        }

        $appt   = Appointment::create([
            'tenant_id'            => $tenant->id,
            'property_id'          => $request->property_id,
            'staff_member_id'      => $staffMemberId,
            'visitor_name'         => $request->visitor_name,
            'visitor_email'        => $request->visitor_email,
            'visitor_phone'        => $request->visitor_phone,
            'appointment_date'     => $request->appointment_date,
            'appointment_time'     => $request->appointment_time ?? '09:00:00',
            'appointment_type'     => $request->appointment_type ?? 'showing',
            'notes'                => $request->message,
            'status'               => 'pending',
            'confirmation_token'   => Str::random(40),
        ]);

        // Always notify the account owner
        $ownerEmail = $tenant->email;
        $subject    = 'New Appointment Request from ' . $request->visitor_name;
        $body       = "New appointment request from {$request->visitor_name} ({$request->visitor_email})\n" .
                      "Phone: {$request->visitor_phone}\n" .
                      "Date: {$request->appointment_date}\nType: {$request->appointment_type}\n" .
                      "Message: {$request->message}";
        TenantMailer::send($tenant->id, $ownerEmail, $subject, $body);

        // Pro: also notify the assigned staff member
        if ($staffEmail && $staffEmail !== $ownerEmail) {
            $propTitle  = isset($property) ? $property->title : 'a property';
            $bodyStaff  = "New appointment request for your listing: {$propTitle}\n\n" . $body;
            TenantMailer::send($tenant->id, $staffEmail, $subject, $bodyStaff);
        }

        return response()->json(['success' => true, 'message' => 'Appointment request sent!']);
    }

    public function action($account, Request $request, $id)
    {
        $tenant = app('tenant');
        $appt   = Appointment::where('tenant_id', $tenant->id)->findOrFail($id);
        $appt->update(['status' => $request->status]);

        // Send confirmation email to visitor
        if ($request->status === 'confirmed' && $appt->visitor_email) {
            $tenant = app('tenant');
            $link   = url("/{$tenant->slug}/confirm-appointment/{$appt->confirmation_token}");
            TenantMailer::send(
                $tenant->id,
                $appt->visitor_email,
                'Appointment Confirmed',
                "Your appointment has been confirmed!\nDate: {$appt->appointment_date}\n\nConfirm here: {$link}"
            );
        }

        return redirect()->back()->with('success', 'Appointment updated.');
    }

    public function bulk($account, Request $request)
    {
        $tenant = app('tenant');
        $ids    = array_map('intval', $request->ids ?? []);
        if (empty($ids)) return redirect()->back();

        if ($request->action === 'delete') {
            Appointment::where('tenant_id', $tenant->id)->whereIn('id', $ids)->delete();
        } elseif ($request->action) {
            Appointment::where('tenant_id', $tenant->id)->whereIn('id', $ids)->update(['status' => $request->action]);
        }
        return redirect()->back()->with('success', 'Done.');
    }
}
