<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Message;
use App\Models\Property;
use App\Models\SiteSettings;
use App\Services\TenantMailer;
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
            // Always notify the account owner
            $ownerEmail = $settings?->contact_email ?: $tenant->email;
            $subject    = 'New Contact Message from ' . $request->name;
            $body       = "New contact message from {$request->name} ({$request->email}):\nPhone: {$request->phone}\n\n{$request->message}";
            TenantMailer::send($tenant->id, $ownerEmail, $subject, $body);

            // Pro: also notify the staff member assigned to the property
            if ($tenant->isPro() && $request->property_id) {
                $property = Property::with('staffMember')->find($request->property_id);
                if ($property && $property->staffMember && $property->staffMember->email) {
                    $staffEmail = $property->staffMember->email;
                    if ($staffEmail !== $ownerEmail) {
                        $bodyStaff = "New contact message regarding your listing: {$property->title}\n\n" . $body;
                        TenantMailer::send($tenant->id, $staffEmail, $subject, $bodyStaff);
                    }
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'Message sent successfully!']);
    }
}
