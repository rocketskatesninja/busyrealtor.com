<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Integration;
use App\Models\Property;
use App\Services\GoogleCalendarService;
use App\Services\TenantMailer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

    public function store($account, Request $request)
    {
        $tenant = app('tenant');

        $request->validate([
            'visitor_name'     => 'required|string|max:255',
            'visitor_email'    => 'nullable|email|max:255',
            'visitor_phone'    => 'nullable|string|max:30',
            'appointment_date' => 'required|date',
            'appointment_time' => 'nullable|date_format:H:i',
            'appointment_type' => 'required|string',
            'property_id'      => 'nullable|integer|exists:properties,id',
            'staff_member_id'  => 'nullable|integer|exists:staff_members,id',
            'notes'            => 'nullable|string|max:2000',
            'status'           => 'required|in:pending,confirmed',
        ]);

        $appt = Appointment::create([
            'tenant_id'        => $tenant->id,
            'property_id'      => $request->property_id ?: null,
            'staff_member_id'  => $request->staff_member_id ?: null,
            'visitor_name'     => $request->visitor_name,
            'visitor_email'    => $request->visitor_email,
            'visitor_phone'    => $request->visitor_phone,
            'appointment_date' => $request->appointment_date,
            'appointment_time' => $request->appointment_time ? $request->appointment_time . ':00' : '09:00:00',
            'appointment_type' => $request->appointment_type,
            'notes'            => $request->notes,
            'status'           => $request->status,
            'source'           => 'admin',
        ]);

        logActivity('created', "Created appointment for {$appt->visitor_name}", $appt);

        // Shared formatting
        $typeLabel = ucwords(str_replace('_', ' ', $appt->appointment_type));
        $dateLabel = Carbon::parse($appt->appointment_date)->format('l, F j, Y');
        $timeLabel = Carbon::parse($appt->appointment_time)->format('g:i A');
        $propLabel = null;
        if ($appt->property_id) {
            $prop = Property::find($appt->property_id);
            if ($prop) $propLabel = "{$prop->title} — {$prop->address_street}, {$prop->address_city}";
        }

        // Email visitor
        if ($appt->visitor_email && $request->boolean('send_visitor_email')) {
            if ($appt->status === 'confirmed') {
                $body  = "Your appointment has been confirmed!\n";
                $body .= str_repeat('─', 40) . "\n";
                $body .= "Type: {$typeLabel}\n";
                $body .= "Date: {$dateLabel} at {$timeLabel}\n";
                if ($propLabel) $body .= "Property: {$propLabel}\n";
                $body .= "\nWe look forward to seeing you! Please reply to this email if you need to make any changes.";
                TenantMailer::send($tenant->id, $appt->visitor_email, 'Appointment Confirmed', $body);
            } else {
                $body  = "Your appointment request has been received.\n";
                $body .= str_repeat('─', 40) . "\n";
                $body .= "Type: {$typeLabel}\n";
                $body .= "Date: {$dateLabel} at {$timeLabel}\n";
                if ($propLabel) $body .= "Property: {$propLabel}\n";
                $body .= "\nWe'll confirm your appointment shortly. Please reply to this email if you have any questions.";
                TenantMailer::send($tenant->id, $appt->visitor_email, 'Appointment Request Received', $body);
            }
        }

        // Email admin
        if ($request->boolean('send_admin_email')) {
            $ownerEmail = $tenant->ownerEmail();
            if ($ownerEmail) {
                $adminBody  = "New appointment created (by admin)\n";
                $adminBody .= str_repeat('─', 40) . "\n";
                $adminBody .= "Client: {$appt->visitor_name}\n";
                if ($appt->visitor_email) $adminBody .= "Email: {$appt->visitor_email}\n";
                if ($appt->visitor_phone) $adminBody .= "Phone: {$appt->visitor_phone}\n";
                $adminBody .= "Type: {$typeLabel}\n";
                $adminBody .= "Date: {$dateLabel} at {$timeLabel}\n";
                if ($propLabel) $adminBody .= "Property: {$propLabel}\n";
                $adminBody .= "Status: " . ucfirst($appt->status) . "\n";
                TenantMailer::send($tenant->id, $ownerEmail, "New Appointment: {$appt->visitor_name} — {$typeLabel}", $adminBody);
            }
        }

        // Google Calendar
        if ($request->boolean('send_calendar') && $appt->status === 'confirmed') {
            try {
                $gcalIntegration = Integration::where('tenant_id', $tenant->id)
                    ->where('integration_type', 'google_calendar')
                    ->where('is_active', true)
                    ->first();

                if ($gcalIntegration && $tenant->isPro()) {
                    $gcalService = new GoogleCalendarService($gcalIntegration);
                    $eventId = $gcalService->createEvent($appt);
                    if ($eventId) {
                        $appt->update(['google_calendar_event_id' => $eventId]);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Google Calendar sync failed on admin create', ['appointment_id' => $appt->id, 'error' => $e->getMessage()]);
            }
        }

        return redirect()->back()->with('success', 'Appointment created.');
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

        if (!in_array($request->status, ['confirmed', 'cancelled'])) {
            return redirect()->back()->with('success', 'Appointment updated.');
        }

        // Shared formatting for emails
        $typeLabel = ucwords($appt->appointment_type ?? 'showing');
        $dateLabel = Carbon::parse($appt->appointment_date)->format('l, F j, Y');
        $timeLabel = Carbon::parse($appt->appointment_time)->format('g:i A');
        $propLabel = null;
        if ($appt->property_id) {
            $prop = Property::find($appt->property_id);
            if ($prop) $propLabel = "{$prop->title} — {$prop->address_street}, {$prop->address_city}";
        }

        // Email visitor
        if ($appt->visitor_email && $request->boolean('send_visitor_email')) {
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

        // Email admin a copy
        if ($request->boolean('send_admin_email')) {
            $ownerEmail = $tenant->ownerEmail();
            if ($ownerEmail) {
                $statusWord = $request->status === 'confirmed' ? 'Confirmed' : 'Cancelled';
                $adminBody  = "Appointment {$request->status}\n";
                $adminBody .= str_repeat('─', 40) . "\n";
                $adminBody .= "Visitor: {$appt->visitor_name}\n";
                $adminBody .= "Email: {$appt->visitor_email}\n";
                if ($appt->visitor_phone) $adminBody .= "Phone: {$appt->visitor_phone}\n";
                $adminBody .= "Type: {$typeLabel}\n";
                $adminBody .= "Date: {$dateLabel} at {$timeLabel}\n";
                if ($propLabel) $adminBody .= "Property: {$propLabel}\n";
                TenantMailer::send($tenant->id, $ownerEmail, "{$statusWord}: {$appt->visitor_name} — {$typeLabel}", $adminBody);
            }
        }

        // Google Calendar — create on confirm, delete on cancel
        if ($request->boolean('send_calendar') || ($request->status === 'cancelled' && $appt->google_calendar_event_id)) {
            try {
                $gcalIntegration = Integration::where('tenant_id', $tenant->id)
                    ->where('integration_type', 'google_calendar')
                    ->where('is_active', true)
                    ->first();

                if ($gcalIntegration && $tenant->isPro()) {
                    $gcalService = new GoogleCalendarService($gcalIntegration);

                    if ($request->status === 'confirmed') {
                        $eventId = $gcalService->createEvent($appt);
                        if ($eventId) {
                            $appt->update(['google_calendar_event_id' => $eventId]);
                        }
                    } elseif ($request->status === 'cancelled' && $appt->google_calendar_event_id) {
                        $gcalService->deleteEvent($appt->google_calendar_event_id);
                        $appt->update(['google_calendar_event_id' => null]);
                    }
                }
            } catch (\Throwable $e) {
                Log::error('Google Calendar sync failed', ['appointment_id' => $appt->id, 'error' => $e->getMessage()]);
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
