<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Property;
use App\Models\SiteSettings;
use App\Services\TenantMailer;
use Carbon\Carbon;
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
        if ($request->search)     $query->where(function ($q) use ($request) {
            $q->where('visitor_name',  'like', '%' . $request->search . '%')
              ->orWhere('visitor_email', 'like', '%' . $request->search . '%');
        });

        match ($request->sort) {
            'oldest'    => $query->orderBy('appointment_date', 'asc'),
            'newest'    => $query->orderBy('appointment_date', 'desc'),
            'created'   => $query->latest(),
            default     => $query->orderBy('appointment_date', 'asc'),
        };

        $appointments = $query->paginate(25)->withQueryString();
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

        // Rate limit: max 3 appointment requests per email per 24 hours
        $recentAppts = Appointment::where('tenant_id', $tenant->id)
            ->where('visitor_email', $request->visitor_email)
            ->where('created_at', '>=', now()->subDay())
            ->count();
        if ($recentAppts >= 3) {
            return response()->json([
                'success' => false,
                'message' => "You've already submitted several appointment requests. Please call or email us directly.",
            ], 429);
        }

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
        $settings   = \App\Models\SiteSettings::where('tenant_id', $tenant->id)->first();
        $ownerEmail = $tenant->ownerEmail();
        $typeLabel  = ucwords($appt->appointment_type);
        $dateLabel  = Carbon::parse($appt->appointment_date)->format('l, F j, Y');
        $timeLabel  = Carbon::parse($appt->appointment_time)->format('g:i A');
        $propLabel  = isset($property) ? "{$property->title} — {$property->address_street}, {$property->address_city}" : null;

        $subject = "New {$typeLabel} Request — {$request->visitor_name}";
        $body  = "New appointment request\n";
        $body .= str_repeat('─', 40) . "\n";
        $body .= "Name: {$appt->visitor_name}\n";
        $body .= "Email: {$appt->visitor_email}\n";
        if ($appt->visitor_phone) $body .= "Phone: {$appt->visitor_phone}\n";
        $body .= "Type: {$typeLabel}\n";
        $body .= "Date: {$dateLabel} at {$timeLabel}\n";
        if ($propLabel) $body .= "Property: {$propLabel}\n";
        if ($appt->notes) $body .= "Notes: {$appt->notes}\n";
        $body .= "\nView in admin: " . route('tenant.admin.appointments.index', $tenant->slug);

        TenantMailer::send($tenant->id, $ownerEmail, $subject, $body);

        // Pro: also notify the assigned staff member
        if ($staffEmail && $staffEmail !== $ownerEmail) {
            TenantMailer::send($tenant->id, $staffEmail, $subject, $body);
        }

        return response()->json(['success' => true, 'message' => 'Appointment request sent!']);
    }

    public function action($account, Request $request, $id)
    {
        $tenant = app('tenant');
        $appt   = Appointment::where('tenant_id', $tenant->id)->findOrFail($id);

        if ($request->status === 'delete') {
            logActivity('deleted', "Deleted appointment with {$appt->visitor_name}", $appt);
            $appt->delete();
            return redirect()->back()->with('success', 'Appointment deleted.');
        }

        $appt->update(['status' => $request->status]);
        logActivity('updated', "Changed appointment with {$appt->visitor_name} to {$request->status}", $appt);

        // Send confirmation/cancellation email to visitor
        if (in_array($request->status, ['confirmed', 'cancelled']) && $appt->visitor_email) {
            $typeLabel = ucwords($appt->appointment_type);
            $dateLabel = Carbon::parse($appt->appointment_date)->format('l, F j, Y');
            $timeLabel = Carbon::parse($appt->appointment_time)->format('g:i A');
            $propLabel = null;
            if ($appt->property_id) {
                $prop = \App\Models\Property::find($appt->property_id);
                if ($prop) $propLabel = "{$prop->title} — {$prop->address_street}, {$prop->address_city}";
            }

            if ($request->status === 'confirmed') {
                $body  = "Your appointment has been confirmed!\n";
                $body .= str_repeat('─', 40) . "\n";
                $body .= "Type: {$typeLabel}\n";
                $body .= "Date: {$dateLabel} at {$timeLabel}\n";
                if ($propLabel) $body .= "Property: {$propLabel}\n";
                $body .= "\nWe look forward to seeing you! Please reply to this email if you need to make any changes.";
                TenantMailer::send($tenant->id, $appt->visitor_email, 'Appointment Confirmed', $body);
            } else {
                $body  = "Your appointment has been cancelled.\n";
                $body .= str_repeat('─', 40) . "\n";
                $body .= "Type: {$typeLabel}\n";
                $body .= "Date: {$dateLabel} at {$timeLabel}\n";
                if ($propLabel) $body .= "Property: {$propLabel}\n";
                $body .= "\nIf you'd like to reschedule, please contact us or visit our website.";
                TenantMailer::send($tenant->id, $appt->visitor_email, 'Appointment Cancelled', $body);
            }
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
        $count = count($ids);
        logActivity($request->action === 'delete' ? 'deleted' : 'updated', "Bulk {$request->action}: {$count} appointments");
        return redirect()->back()->with('success', 'Done.');
    }
}
