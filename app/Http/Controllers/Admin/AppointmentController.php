<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Integration;
use App\Models\Property;
use App\Models\StaffMember;
use App\Services\GoogleCalendarService;
use App\Services\TenantMailer;
use App\Support\MailBody;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AppointmentController extends Controller
{
    public function index($account, Request $request)
    {
        $tenant = app('tenant');
        $query  = Appointment::where('tenant_id', $tenant->id)->with('property');

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

    public function storePublic($account, \App\Http\Requests\StorePublicAppointmentRequest $request)
    {
        $tenant = app('tenant');
        if (!$tenant->isPro()) {
            return response()->json(['success' => false, 'message' => 'Appointment booking is not available for this agency.'], 403);
        }

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

        // Resolve assigned staff member from property
        $staffMemberId = null;
        $staffEmail    = null;
        if ($request->property_id) {
            $property = Property::with('staffMember')->find($request->property_id);
            if ($property && $property->staff_member_id) {
                $staffMemberId = $property->staff_member_id;
                if ($property->staffMember && $property->staffMember->email) {
                    $staffEmail = $property->staffMember->email;
                }
            }
        }

        $appt = Appointment::create([
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
        ]);

        $fmt = self::formatAppt($appt);

        // Notify account owner
        $ownerEmail = $tenant->ownerEmail();
        $subject = "New {$fmt['type']} Request — {$appt->visitor_name}";
        $body = self::buildEmailBody("New appointment request", $appt, $fmt, [
            'admin_link' => route('tenant.admin.appointments.index', $tenant->slug),
        ]);
        TenantMailer::send($tenant->id, $ownerEmail, $subject, $body);

        // Notify assigned staff member
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

        // Assign staff member to the property — TENANT SCOPED. Without
        // the tenant_id filter, a tenant-A admin could pass a property_id
        // belonging to tenant B and reassign tenant B's staff member.
        if ($request->staff_member_id && $request->property_id) {
            Property::where('id', $request->property_id)
                ->where('tenant_id', $tenant->id)
                ->update(['staff_member_id' => $request->staff_member_id]);
        }

        $fmt = self::formatAppt($appt);

        // Email visitor
        if ($appt->visitor_email && $request->boolean('send_visitor_email')) {
            $isConfirmed = $appt->status === 'confirmed';
            $subject = $isConfirmed ? 'Appointment Confirmed' : 'Appointment Request Received';
            $headline = $isConfirmed
                ? "Your appointment has been confirmed!"
                : "Your appointment request has been received.";
            $footer = $isConfirmed
                ? "We look forward to seeing you! Please reply to this email if you need to make any changes."
                : "We'll confirm your appointment shortly. Please reply to this email if you have any questions.";
            $body = self::buildEmailBody($headline, $appt, $fmt, ['footer' => $footer, 'visitor' => true]);
            $agent = self::resolveAgent($appt, $tenant);
            TenantMailer::send($tenant->id, $appt->visitor_email, $subject, $body, 'tenant', null, null, $agent);
        }

        // Email admin
        if ($request->boolean('send_admin_email')) {
            $ownerEmail = $tenant->ownerEmail();
            if ($ownerEmail) {
                $body = self::buildEmailBody("New appointment created (by admin)", $appt, $fmt, ['include_status' => true]);
                TenantMailer::send($tenant->id, $ownerEmail, "New Appointment: {$appt->visitor_name} — {$fmt['type']}", $body);
            }
        }

        // Email assigned staff
        self::sendStaffEmail($tenant, $appt, $fmt, $request->boolean('send_staff_email'), 'assigned');

        // Google Calendar
        self::syncCalendar($tenant, $appt, $request->boolean('send_calendar'), 'confirmed');

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

        $fmt = self::formatAppt($appt);
        $isConfirmed = $request->status === 'confirmed';
        $statusWord  = $isConfirmed ? 'Confirmed' : 'Cancelled';

        // Email visitor
        if ($appt->visitor_email && $request->boolean('send_visitor_email')) {
            $headline = $isConfirmed
                ? "Your appointment has been confirmed!"
                : "Your appointment has been cancelled.";
            $footer = $isConfirmed
                ? "We look forward to seeing you! Please reply to this email if you need to make any changes."
                : "If you'd like to reschedule, please contact us or visit our website.";
            $body = self::buildEmailBody($headline, $appt, $fmt, ['footer' => $footer, 'visitor' => true]);
            $agent = self::resolveAgent($appt, $tenant);
            TenantMailer::send($tenant->id, $appt->visitor_email, "Appointment {$statusWord}", $body, 'tenant', null, null, $agent);
        }

        // Email admin
        if ($request->boolean('send_admin_email')) {
            $ownerEmail = $tenant->ownerEmail();
            if ($ownerEmail) {
                $body = self::buildEmailBody("Appointment {$request->status}", $appt, $fmt);
                TenantMailer::send($tenant->id, $ownerEmail, "{$statusWord}: {$appt->visitor_name} — {$fmt['type']}", $body);
            }
        }

        // Email assigned staff
        $action = $isConfirmed ? 'confirmed' : 'cancelled';
        self::sendStaffEmail($tenant, $appt, $fmt, $request->boolean('send_staff_email'), $action);

        // Google Calendar
        self::syncCalendar($tenant, $appt, $request->boolean('send_calendar'), $request->status);

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

    /**
     * Resolve agent info for email cards: staff member if assigned, else tenant owner.
     */
    private static function resolveAgent(Appointment $appt, $tenant): ?array
    {
        $settings = \App\Models\SiteSettings::where('tenant_id', $tenant->id)->first();

        if ($appt->staff_member_id) {
            // Defense in depth — even though $appt is already tenant-scoped
            // by the caller, scope the staff lookup too so a future caller
            // bug can't turn this into a cross-tenant leak.
            $staff = StaffMember::where('tenant_id', $tenant->id)->find($appt->staff_member_id);
            if ($staff) {
                return [
                    'name'  => $staff->name,
                    'title' => $staff->title ?? null,
                    'email' => $staff->email ?? null,
                    'phone' => $staff->phone ?? null,
                    'photo' => $staff->photo_url ? asset('storage/' . $staff->photo_url) : null,
                ];
            }
        }

        if ($settings && $settings->owner_name) {
            return [
                'name'  => $settings->owner_name,
                'title' => null,
                'email' => $settings->contact_email ?? null,
                'phone' => $settings->contact_phone ?? null,
                'photo' => $settings->owner_photo ? asset('storage/' . $settings->owner_photo) : null,
            ];
        }

        return null;
    }

    // ── Helpers ────────────────────────────────────────────────────────

    /**
     * Format appointment fields for email templates.
     */
    private static function formatAppt(Appointment $appt): array
    {
        $propLabel = null;
        if ($appt->property_id) {
            // Defense in depth — scope by the appointment's own tenant_id
            // since $tenant is not in scope inside this static helper.
            $prop = Property::where('tenant_id', $appt->tenant_id)->find($appt->property_id);
            if ($prop) $propLabel = "{$prop->title} — {$prop->address_street}, {$prop->address_city}";
        }

        return [
            'type'     => ucwords(str_replace('_', ' ', $appt->appointment_type ?? 'showing')),
            'date'     => Carbon::parse($appt->appointment_date)->format('l, F j, Y'),
            'time'     => Carbon::parse($appt->appointment_time)->format('g:i A'),
            'property' => $propLabel,
        ];
    }

    /**
     * Build a plain-text email body for appointment notifications.
     */
    private static function buildEmailBody(string $headline, Appointment $appt, array $fmt, array $opts = []): string
    {
        $isVisitor = $opts['visitor'] ?? false;

        $body = MailBody::make($headline);

        if (!$isVisitor) {
            $body->row('Client', $appt->visitor_name)
                 ->row('Email',  $appt->visitor_email)
                 ->row('Phone',  $appt->visitor_phone);
        }

        $body->row('Type', $fmt['type'])
             ->row('Date', "{$fmt['date']} at {$fmt['time']}")
             ->row('Property', $fmt['property'] ?: null);

        if (!empty($opts['include_status'])) {
            $body->row('Status', ucfirst($appt->status));
        }

        if (!empty($opts['admin_link'])) {
            $body->row('Notes', $appt->notes)
                 ->blank()
                 ->line("View in admin: {$opts['admin_link']}");
        }

        if (!empty($opts['footer'])) {
            $body->blank()->line($opts['footer']);
        }

        return $body->toString();
    }

    /**
     * Send email to assigned staff member if requested.
     */
    private static function sendStaffEmail($tenant, Appointment $appt, array $fmt, bool $send, string $action): void
    {
        if (!$send || !$appt->staff_member_id) return;

        // Defense in depth — scope the staff lookup by current tenant.
        $staff = StaffMember::where('tenant_id', $tenant->id)->find($appt->staff_member_id);
        if (!$staff || !$staff->email) return;

        $headlines = [
            'assigned'  => "You've been assigned a new appointment.",
            'confirmed' => "An appointment you're assigned to has been confirmed.",
            'cancelled' => "An appointment you're assigned to has been cancelled.",
        ];

        $body = self::buildEmailBody($headlines[$action] ?? $headlines['assigned'], $appt, $fmt, ['include_status' => true]);
        $subject = match($action) {
            'confirmed' => "Appointment Confirmed: {$appt->visitor_name} — {$fmt['type']}",
            'cancelled' => "Appointment Cancelled: {$appt->visitor_name} — {$fmt['type']}",
            default     => "Appointment Assigned: {$appt->visitor_name} — {$fmt['type']}",
        };

        TenantMailer::send($tenant->id, $staff->email, $subject, $body);
    }

    /**
     * Sync appointment with Google Calendar (create on confirm, delete on cancel).
     */
    private static function syncCalendar($tenant, Appointment $appt, bool $send, string $status): void
    {
        if (!$send && !($status === 'cancelled' && $appt->google_calendar_event_id)) return;

        try {
            $gcalIntegration = $tenant->getIntegration('google_calendar', true);

            if (!$gcalIntegration || !$tenant->isPro()) return;

            $gcalService = new GoogleCalendarService($gcalIntegration);

            if ($status === 'confirmed') {
                $eventId = $gcalService->createEvent($appt);
                if ($eventId) $appt->update(['google_calendar_event_id' => $eventId]);
            } elseif ($status === 'cancelled' && $appt->google_calendar_event_id) {
                $gcalService->deleteEvent($appt->google_calendar_event_id);
                $appt->update(['google_calendar_event_id' => null]);
            }
        } catch (\Throwable $e) {
            Log::error('Google Calendar sync failed', ['appointment_id' => $appt->id, 'error' => $e->getMessage()]);
        }
    }
}
