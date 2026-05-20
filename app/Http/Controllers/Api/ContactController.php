<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Property;
use App\Models\SiteSettings;
use App\Services\TenantMailer;
use App\Support\MailBody;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function submit($account, Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:255',
            'email'      => 'required|email',
            'phone'      => 'nullable|string|max:30',
            'message'    => 'required|string|max:5000',
            'property_id'=> 'nullable|integer',
        ]);

        $tenant = app('tenant');

        // Rate limit: max 3 contact messages per email per hour
        $recentFromEmail = Message::where('tenant_id', $tenant->id)
            ->where('sender_email', $request->email)
            ->where('created_at', '>=', now()->subHour())
            ->count();
        if ($recentFromEmail >= 3) {
            return response()->json([
                'success' => false,
                'error' => "You've already sent several messages. Please wait or call us directly.",
            ], 429);
        }

        // Rate limit: max 10 contact messages per IP per hour
        $ipKey = 'contact_ip_' . $tenant->id . '_' . md5($request->ip());
        $ipCount = (int) cache($ipKey, 0);
        if ($ipCount >= 10) {
            return response()->json([
                'success' => false,
                'error' => "Too many messages from this connection. Please try again later or call us.",
            ], 429);
        }
        cache([$ipKey => $ipCount + 1], now()->addHour());

        Message::create([
            'tenant_id'    => $tenant->id,
            'property_id'  => $request->property_id,
            'source'       => 'contact_form',
            'sender_name'  => $request->name,
            'sender_email' => $request->email,
            'sender_phone' => $request->phone,
            'message'      => $request->message,
            'status'       => 'new',
            'is_read'      => false,
        ]);

        $settings = SiteSettings::where('tenant_id', $tenant->id)->first();
        $notifyEnabled = !$settings || $settings->notify_on_contact !== false;

        if ($notifyEnabled) {
            $ownerEmail = $tenant->ownerEmail();
            $subject    = 'New Contact Message from ' . $request->name;

            $body = MailBody::make('New contact form submission')
                ->row('Name',  $request->name)
                ->row('Email', $request->email)
                ->row('Phone', $request->phone);

            // Look up property for label and staff notification
            $property  = null;
            $propLabel = null;
            if ($request->property_id) {
                $property = Property::with('staffMember')->find($request->property_id);
                if ($property) $propLabel = "{$property->title} — {$property->address_street}, {$property->address_city}";
            }
            $body->row('Property', $propLabel);
            $body->blank()->line($request->message);

            $bodyStr = $body->toString();

            TenantMailer::send($tenant->id, $ownerEmail, $subject, $bodyStr);

            // Pro: also notify the assigned staff member
            if ($tenant->isPro() && $property && $property->staffMember && $property->staffMember->email) {
                $staffEmail = $property->staffMember->email;
                if ($staffEmail !== $ownerEmail) {
                    TenantMailer::send($tenant->id, $staffEmail, $subject, $bodyStr);
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'Message sent successfully!']);
    }
}
