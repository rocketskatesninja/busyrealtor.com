<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ChatLog;
use App\Models\Integration;
use App\Models\SiteSettings;
use App\Models\Property;
use App\Services\TenantMailer;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    public function chat($account, Request $request)
    {
        $request->validate([
            'message'    => 'required|string|max:2000',
            'session_id' => 'nullable|string',
        ]);

        $tenant = app('tenant');
        if (!$tenant->isPro()) {
            return response()->json(['reply' => 'The AI chatbot is available on the Pro plan. Please upgrade your account.']);
        }

        $settings = SiteSettings::where('tenant_id', $tenant->id)->first();
        $aiInteg  = Integration::where('tenant_id', $tenant->id)
                        ->where('integration_type', 'ai_provider')
                        ->where('is_active', true)
                        ->first();

        if (!$aiInteg || !$aiInteg->api_key) {
            return response()->json(['reply' => 'Chatbot is not configured yet.']);
        }

        $sessionId = $request->session_id ?? Str::uuid()->toString();
        $history   = ChatLog::where('session_id', $sessionId)->orderBy('created_at')->get();

        // Build system prompt
        $activeProps = Property::where('tenant_id', $tenant->id)
            ->where('listing_status', 'active')->limit(6)->get()
            ->map(fn($p) => "- {$p->title} at {$p->address_street}, {$p->address_city} (\${$p->price})")->implode("\n");

        $sysPrompt  = "You are a friendly real estate assistant for {$tenant->name}. ";
        if ($settings?->chatbot_realtor_bio) {
            $sysPrompt .= "About the realtor: {$settings->chatbot_realtor_bio}. ";
        }
        if ($settings) {
            $sysPrompt .= "Office contact: {$settings->contact_email}, {$settings->contact_phone}. ";
        }
        if ($activeProps) {
            $sysPrompt .= "Active listings:\n{$activeProps}\n";
        }
        $sysPrompt .= "\nWhen a visitor wants to schedule a showing, consultation, or any appointment, "
                    . "collect their full name, email address, preferred date, and appointment type "
                    . "(showing, consultation, virtual, or other). Ask for one missing piece of info at a time. "
                    . "Once you have the required details, call the book_appointment tool immediately. "
                    . "Do not ask for confirmation before calling it — just book it. Be concise and warm.";

        $messages = $history->map(fn($log) => [
            'role'    => $log->role,
            'content' => $log->content,
        ])->all();
        $messages[] = ['role' => 'user', 'content' => $request->message];

        $config = $aiInteg->config ?? [];
        $reply  = '';
        $booked = false;

        try {
            if ($aiInteg->provider === 'anthropic') {
                [$reply, $booked] = $this->callAnthropic($aiInteg->api_key, $config, $sysPrompt, $messages, $tenant, $settings, $request->message);
            } else {
                [$reply, $booked] = $this->callOpenAI($aiInteg->api_key, $config, $sysPrompt, $messages, $tenant, $settings, $request->message);
            }
        } catch (\Exception $e) {
            Log::error('Chatbot exception', ['error' => $e->getMessage()]);
            $reply = 'Sorry, I am having trouble right now. Please call or email us directly.';
        }

        ChatLog::create(['tenant_id' => $tenant->id, 'session_id' => $sessionId, 'role' => 'user',      'content' => $request->message]);
        ChatLog::create(['tenant_id' => $tenant->id, 'session_id' => $sessionId, 'role' => 'assistant', 'content' => $reply]);

        return response()->json(['reply' => $reply, 'session_id' => $sessionId, 'booked' => $booked]);
    }

    // ── Anthropic ──────────────────────────────────────────────────────────────

    private function callAnthropic(string $key, array $config, string $sys, array $messages, $tenant, $settings, string $lastMessage = ''): array
    {
        $resp = Http::withHeaders([
            'x-api-key'         => $key,
            'anthropic-version' => '2023-06-01',
        ])->post('https://api.anthropic.com/v1/messages', [
            'model'      => $config['model'] ?? 'claude-haiku-4-5-20251001',
            'max_tokens' => 600,
            'system'     => $sys,
            'messages'   => $messages,
            'tools'      => [$this->anthropicTool()],
        ]);

        if ($resp->failed()) {
            Log::error('Chatbot Anthropic error', ['status' => $resp->status(), 'body' => $resp->body()]);
            return ['Sorry, I am having trouble right now. Please contact us directly.', false];
        }

        $data    = $resp->json();
        $content = $data['content'] ?? [];

        // Check for tool_use block
        $toolBlock = collect($content)->firstWhere('type', 'tool_use');
        if (($data['stop_reason'] ?? '') === 'tool_use' && $toolBlock) {
            $reply = $this->createAppointment($toolBlock['input'] ?? [], $tenant, $settings, $lastMessage);
            return [$reply, true];
        }

        $textBlock = collect($content)->firstWhere('type', 'text');
        return [$textBlock['text'] ?? 'Sorry, I could not respond.', false];
    }

    private function anthropicTool(): array
    {
        return [
            'name'        => 'book_appointment',
            'description' => 'Book a property appointment. Call this as soon as you have the visitor\'s name, email, preferred date, and appointment type.',
            'input_schema' => [
                'type'       => 'object',
                'properties' => [
                    'visitor_name'     => ['type' => 'string',  'description' => 'Full name of the visitor'],
                    'visitor_email'    => ['type' => 'string',  'description' => 'Email address'],
                    'visitor_phone'    => ['type' => 'string',  'description' => 'Phone number (optional)'],
                    'appointment_type' => ['type' => 'string',  'enum' => ['showing', 'consultation', 'virtual', 'other']],
                    'appointment_date' => ['type' => 'string',  'description' => 'Preferred date, YYYY-MM-DD'],
                    'property_id'      => ['type' => 'integer', 'description' => 'ID of the property the visitor is interested in, if known'],
                    'notes'            => ['type' => 'string',  'description' => 'Additional notes'],
                ],
                'required' => ['visitor_name', 'visitor_email', 'appointment_date', 'appointment_type'],
            ],
        ];
    }

    // ── OpenAI ─────────────────────────────────────────────────────────────────

    private function callOpenAI(string $key, array $config, string $sys, array $messages, $tenant, $settings, string $lastMessage = ''): array
    {
        $resp = Http::withToken($key)->post('https://api.openai.com/v1/chat/completions', [
            'model'       => $config['model'] ?? 'gpt-4o-mini',
            'max_tokens'  => 600,
            'messages'    => array_merge([['role' => 'system', 'content' => $sys]], $messages),
            'tools'       => [$this->openAITool()],
            'tool_choice' => 'auto',
        ]);

        if ($resp->failed()) {
            Log::error('Chatbot OpenAI error', ['status' => $resp->status(), 'body' => $resp->body()]);
            return ['Sorry, I am having trouble right now. Please contact us directly.', false];
        }

        $choice  = $resp->json('choices.0') ?? [];
        $message = $choice['message'] ?? [];

        if (($choice['finish_reason'] ?? '') === 'tool_calls' && !empty($message['tool_calls'])) {
            $toolCall = collect($message['tool_calls'])->firstWhere('function.name', 'book_appointment');
            if ($toolCall) {
                $input = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?? [];
                $reply = $this->createAppointment($input, $tenant, $settings, $lastMessage);
                return [$reply, true];
            }
        }

        return [$message['content'] ?? 'Sorry, I could not respond.', false];
    }

    private function openAITool(): array
    {
        return [
            'type'     => 'function',
            'function' => [
                'name'        => 'book_appointment',
                'description' => 'Book a property appointment. Call this as soon as you have the visitor\'s name, email, preferred date, and appointment type.',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'visitor_name'     => ['type' => 'string'],
                        'visitor_email'    => ['type' => 'string'],
                        'visitor_phone'    => ['type' => 'string'],
                        'appointment_type' => ['type' => 'string', 'enum' => ['showing', 'consultation', 'virtual', 'other']],
                        'appointment_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                        'property_id'      => ['type' => 'integer', 'description' => 'ID of the property the visitor is interested in, if known'],
                        'notes'            => ['type' => 'string'],
                    ],
                    'required' => ['visitor_name', 'visitor_email', 'appointment_date', 'appointment_type'],
                ],
            ],
        ];
    }

    // ── Shared appointment creation ────────────────────────────────────────────

    private function createAppointment(array $input, $tenant, $settings, string $lastMessage = ''): string
    {
        try {
            $date = Carbon::parse($input['appointment_date'] ?? 'tomorrow');

            // Resolve property and assigned staff if property_id provided
            $property  = null;
            $staffEmail = null;
            if (!empty($input['property_id'])) {
                $property = Property::with('staffMember')
                    ->where('tenant_id', $tenant->id)
                    ->find((int) $input['property_id']);
                if ($property && $tenant->isPro() && $property->staffMember?->email) {
                    $staffEmail = $property->staffMember->email;
                }
            }

            $appt = Appointment::create([
                'tenant_id'        => $tenant->id,
                'property_id'      => $property?->id,
                'staff_member_id'  => $property?->staff_member_id,
                'visitor_name'     => $input['visitor_name']     ?? 'Unknown',
                'visitor_email'    => $input['visitor_email']    ?? null,
                'visitor_phone'    => $input['visitor_phone']    ?? null,
                'appointment_type' => $input['appointment_type'] ?? 'showing',
                'appointment_date' => $date->format('Y-m-d'),
                'appointment_time' => '10:00:00',
                'status'           => 'pending',
                'notes'            => $this->buildNotes($input, $lastMessage),
                'source'           => 'chatbot',
            ]);

            // Notify owner
            $ownerEmail = $settings?->contact_email ?: $tenant->email;
            if ($ownerEmail) {
                $typeLabel = ucwords(str_replace('_', ' ', $appt->appointment_type));
                $body  = "New appointment request via chatbot\n";
                $body .= str_repeat('─', 40) . "\n";
                $body .= "Name:  {$appt->visitor_name}\n";
                $body .= "Email: {$appt->visitor_email}\n";
                if ($appt->visitor_phone) $body .= "Phone: {$appt->visitor_phone}\n";
                $body .= "Type:  {$typeLabel}\n";
                $body .= "Date:  {$date->format('l, F j, Y')}\n";
                if ($property) $body .= "Property: {$property->title} — {$property->address_street}, {$property->address_city}\n";
                if ($appt->notes) $body .= "Notes: {$appt->notes}\n";
                $body .= "\nView in admin: " . url("/{$tenant->slug}/admin/appointments");

                TenantMailer::send($tenant->id, $ownerEmail, "New {$typeLabel} Request — {$appt->visitor_name}", $body);
            }

            // Pro: also notify the assigned staff member
            if ($staffEmail && $staffEmail !== $ownerEmail) {
                $bodyStaff = "New appointment request for your listing: {$property->title}\n\n" . $body;
                TenantMailer::send($tenant->id, $staffEmail, "New {$typeLabel} Request — {$appt->visitor_name}", $bodyStaff);
            }

            return "Your {$appt->appointment_type} has been requested for {$date->format('l, F j')}. "
                 . "{$tenant->name} will reach out to confirm — watch for an email at {$appt->visitor_email}. "
                 . "Is there anything else I can help with?";

        } catch (\Exception $e) {
            Log::error('Chatbot appointment creation failed', ['error' => $e->getMessage(), 'input' => $input]);
            return "I wasn't able to submit the request right now. Please call or email us directly to schedule.";
        }
    }

    private function buildNotes(array $input, string $lastMessage): string
    {
        $parts = [];
        if (!empty($input['notes'])) {
            $parts[] = $input['notes'];
        }
        if ($lastMessage) {
            $parts[] = 'Visitor message: "' . $lastMessage . '"';
        }
        if (empty($parts)) {
            $parts[] = 'Booked via chatbot.';
        }
        return implode(' | ', $parts);
    }
}
