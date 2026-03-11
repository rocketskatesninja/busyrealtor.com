<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\ChatLog;
use App\Models\Integration;
use App\Models\Message;
use App\Models\Property;
use App\Models\StaffMember;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Services\TenantMailer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AdminChatController extends Controller
{
    public function index($account, Request $request)
    {
        $tenant = app('tenant');

        if (!$tenant->isPro()) {
            return redirect()->route('tenant.admin.billing', $account)
                ->with('error', 'The AI Assistant is available on the Pro plan.');
        }

        $sessionKey = 'ai_chat_' . $tenant->id;

        // "New chat" clears the stored session and redirects
        if ($request->boolean('new')) {
            session()->forget($sessionKey);
            return redirect()->route('tenant.admin.assistant', $account);
        }

        $sessionId = session($sessionKey);
        $chatLogs  = collect();

        if ($sessionId) {
            $chatLogs = ChatLog::where('tenant_id', $tenant->id)
                ->where('session_id', $sessionId)
                ->orderBy('id')
                ->get();
        }

        // No existing session or logs purged — start fresh
        if ($chatLogs->isEmpty()) {
            $sessionId = 'admin_' . Str::uuid()->toString();
            session([$sessionKey => $sessionId]);

            $motd = $this->buildMotd($tenant);
            $log  = ChatLog::create([
                'tenant_id'  => $tenant->id,
                'session_id' => $sessionId,
                'role'       => 'assistant',
                'content'    => $motd,
            ]);
            $chatLogs = collect([$log]);
        }

        ['preferred' => $preferred, 'model' => $modelKey] = $this->resolveAiConfig($tenant);
        $providerLabel = $preferred === 'openai' ? 'OpenAI' : 'Anthropic';
        $modelLabel    = $this->friendlyModelName($modelKey);

        return view('tenant.admin.assistant', compact('tenant', 'account', 'sessionId', 'chatLogs', 'providerLabel', 'modelLabel'));
    }

    public function chat($account, Request $request)
    {
        $request->validate([
            'message'    => 'required|string|max:4000',
            'session_id' => 'required|string|max:100',
        ]);

        $tenant = app('tenant');

        if (!$tenant->isPro()) {
            return response()->json(['error' => 'Pro plan required.'], 403);
        }

        $sessionId = $request->session_id;

        if (!Str::startsWith($sessionId, 'admin_')) {
            return response()->json(['error' => 'Invalid session.'], 400);
        }

        $sessionOwned = ChatLog::where('tenant_id', $tenant->id)
            ->where('session_id', $sessionId)
            ->exists();

        if (!$sessionOwned) {
            return response()->json(['error' => 'Session not found.'], 404);
        }

        ['preferred' => $preferred, 'key' => $key, 'model' => $model, 'integration' => $integration] = $this->resolveAiConfig($tenant);

        if (!$integration) {
            return response()->json(['reply' => 'No AI provider configured. Please add API keys in **Settings → Chatbot & AI**.']);
        }

        if (!$key) {
            $name = $preferred === 'openai' ? 'OpenAI' : 'Anthropic';
            return response()->json(['reply' => "No {$name} API key configured. Please add one in **Settings → Chatbot & AI**."]);
        }

        $history = ChatLog::where('tenant_id', $tenant->id)
            ->where('session_id', $sessionId)
            ->orderBy('id')
            ->get()
            ->map(fn($log) => ['role' => $log->role, 'content' => $log->content])
            ->all();

        $history[] = ['role' => 'user', 'content' => $request->message];

        if (count($history) > 20) {
            $history = array_slice($history, -20);
        }

        ChatLog::create([
            'tenant_id'  => $tenant->id,
            'session_id' => $sessionId,
            'role'       => 'user',
            'content'    => $request->message,
        ]);

        try {
            $reply = $preferred === 'openai'
                ? $this->runOpenAILoop($key, $model, $history, $tenant)
                : $this->runAnthropicLoop($key, $model, $history, $tenant);
        } catch (\Exception $e) {
            Log::error('AdminChat error', ['tenant' => $tenant->id, 'error' => $e->getMessage()]);
            $reply = 'Sorry, I ran into an error. Please try again.';
        }

        ChatLog::create([
            'tenant_id'  => $tenant->id,
            'session_id' => $sessionId,
            'role'       => 'assistant',
            'content'    => $reply,
        ]);

        return response()->json(['reply' => $reply]);
    }

    // ─── Core loop ───────────────────────────────────────────────────────────

    private function runAnthropicLoop(string $key, string $model, array $messages, $tenant): string
    {
        $allowedModels = ['claude-opus-4-6', 'claude-sonnet-4-6', 'claude-haiku-4-5-20251001'];
        if (!in_array($model, $allowedModels)) {
            $model = 'claude-haiku-4-5-20251001';
        }

        $systemPrompt = $this->buildSystemPrompt($tenant);
        $tools        = $this->tools();
        $auditLog     = [];
        $maxIter      = 8;

        for ($i = 0; $i < $maxIter; $i++) {
            $response = Http::withHeaders([
                'x-api-key'         => $key,
                'anthropic-version' => '2023-06-01',
            ])->timeout(45)->post('https://api.anthropic.com/v1/messages', [
                'model'      => $model,
                'max_tokens' => 1024,
                'system'     => $systemPrompt,
                'messages'   => $messages,
                'tools'      => $tools,
            ]);

            if ($response->failed()) {
                Log::error('AdminChat Anthropic HTTP error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return 'I had trouble reaching the AI service. Please try again.';
            }

            $data       = $response->json();
            $stopReason = $data['stop_reason'] ?? 'end_turn';
            $content    = $data['content'] ?? [];

            if ($stopReason === 'end_turn') {
                $textBlock = collect($content)->firstWhere('type', 'text');
                $text = $textBlock['text'] ?? 'Done.';
                if (!empty($auditLog)) {
                    $text = implode("\n", $auditLog) . "\n\n" . $text;
                }
                return $text;
            }

            if ($stopReason === 'tool_use') {
                $messages[] = ['role' => 'assistant', 'content' => $content];

                $toolResults = [];
                foreach ($content as $block) {
                    if ($block['type'] !== 'tool_use') continue;

                    [$result, $audit] = $this->executeTool($block['name'], $block['input'] ?? [], $tenant);

                    if ($audit) {
                        $auditLog[] = $audit;
                    }

                    $toolResults[] = [
                        'type'        => 'tool_result',
                        'tool_use_id' => $block['id'],
                        'content'     => json_encode($result),
                    ];
                }

                $messages[] = ['role' => 'user', 'content' => $toolResults];
                continue;
            }

            // Unexpected stop — return any text we have
            $textBlock = collect($content)->firstWhere('type', 'text');
            return $textBlock['text'] ?? "I'm not sure how to respond to that.";
        }

        return 'I hit a processing limit. Please try rephrasing your request.';
    }

    private function executeTool(string $name, array $input, $tenant): array
    {
        $result = match ($name) {
            'get_overview'       => $this->toolGetOverview($tenant),
            'list_properties'    => $this->toolListProperties($input, $tenant),
            'get_property'       => $this->toolGetProperty($input, $tenant),
            'list_messages'      => $this->toolListMessages($input, $tenant),
            'list_appointments'  => $this->toolListAppointments($input, $tenant),
            'list_staff'         => $this->toolListStaff($tenant),
            'update_property'    => $this->toolUpdateProperty($input, $tenant),
            'update_message'     => $this->toolUpdateMessage($input, $tenant),
            'update_appointment' => $this->toolUpdateAppointment($input, $tenant),
            'create_appointment' => $this->toolCreateAppointment($input, $tenant),
            'send_email'         => $this->toolSendEmail($input, $tenant),
            default              => ['error' => "Unknown tool: {$name}"],
        };

        $audit = null;
        if (isset($result['_audit'])) {
            $audit = $result['_audit'];
            unset($result['_audit']);
        }

        return [$result, $audit];
    }

    // ─── READ tools ──────────────────────────────────────────────────────────

    private function toolGetOverview($tenant): array
    {
        $today = Carbon::today();

        $unreadCount  = Message::where('tenant_id', $tenant->id)->where('is_read', false)->count();
        $recentUnread = Message::where('tenant_id', $tenant->id)->where('is_read', false)
            ->orderByDesc('created_at')->limit(5)->pluck('sender_name');

        $todayAppts = Appointment::where('tenant_id', $tenant->id)
            ->whereDate('appointment_date', $today)
            ->where('status', 'pending')
            ->orderBy('appointment_time')
            ->with('property')
            ->get()
            ->map(fn($a) => [
                'id'               => $a->id,
                'visitor_name'     => $a->visitor_name,
                'appointment_time' => Carbon::parse($a->appointment_time)->format('g:i A'),
                'appointment_type' => $a->appointment_type,
                'property'         => $a->property?->address_street,
                'status'           => $a->status,
            ]);

        return [
            'date'                => $today->toDateString(),
            'unread_messages'     => $unreadCount,
            'recent_senders'      => $recentUnread->all(),
            'todays_appointments' => $todayAppts->all(),
            'active_listings'     => Property::where('tenant_id', $tenant->id)->where('listing_status', 'active')->count(),
            'pending_listings'    => Property::where('tenant_id', $tenant->id)->where('listing_status', 'pending')->count(),
            'sold_listings'       => Property::where('tenant_id', $tenant->id)->where('listing_status', 'sold')->count(),
        ];
    }

    private function toolListProperties(array $input, $tenant): array
    {
        $query = Property::where('tenant_id', $tenant->id);
        if (!empty($input['status']))  $query->where('listing_status', $input['status']);
        if (!empty($input['search'])) {
            $searchTerm = $input['search'];
            $query->where(fn($q) => $q->where('title', 'like', "%{$searchTerm}%")
                ->orWhere('address_street', 'like', "%{$searchTerm}%")
                ->orWhere('address_city', 'like', "%{$searchTerm}%"));
        }
        $limit = min((int) ($input['limit'] ?? 10), 25);

        return $query->orderByDesc('created_at')->limit($limit)->get()
            ->map(fn($p) => [
                'id'             => $p->id,
                'title'          => $p->title,
                'address'        => trim("{$p->address_street}, {$p->address_city}, {$p->address_state}", ', '),
                'price'          => (int) $p->price,
                'listing_status' => $p->listing_status,
                'property_type'  => $p->property_type,
                'bedrooms'       => $p->bedrooms,
                'bathrooms'      => $p->bathrooms,
                'square_feet'    => $p->square_feet,
                'is_featured'    => $p->is_featured,
                'view_count'     => $p->view_count,
            ])->all();
    }

    private function toolGetProperty(array $input, $tenant): array
    {
        $prop = Property::where('tenant_id', $tenant->id)->find((int) ($input['id'] ?? 0));
        if (!$prop) return ['error' => 'Property not found.'];

        return [
            'id'              => $prop->id,
            'title'           => $prop->title,
            'address'         => "{$prop->address_street}, {$prop->address_city}, {$prop->address_state} {$prop->address_zip}",
            'price'           => (int) $prop->price,
            'listing_status'  => $prop->listing_status,
            'property_type'   => $prop->property_type,
            'bedrooms'        => $prop->bedrooms,
            'bathrooms'       => $prop->bathrooms,
            'square_feet'     => $prop->square_feet,
            'description'     => Str::limit($prop->description ?? '', 400),
            'is_featured'     => $prop->is_featured,
            'view_count'      => $prop->view_count,
            'mls_number'      => $prop->mls_number,
            'year_built'      => $prop->year_built,
            'hoa_fee'         => $prop->hoa_fee,
            'garage'          => $prop->garage,
        ];
    }

    private function toolListMessages(array $input, $tenant): array
    {
        $query = Message::where('tenant_id', $tenant->id);
        if (!empty($input['status'])) $query->where('status', $input['status']);
        $limit = min((int) ($input['limit'] ?? 10), 25);

        return $query->orderByDesc('created_at')->limit($limit)->get()
            ->map(fn($m) => [
                'id'           => $m->id,
                'sender_name'  => $m->sender_name,
                'sender_email' => $m->sender_email,
                'message'      => Str::limit($m->message, 300),
                'status'       => $m->status,
                'is_read'      => $m->is_read,
                'is_starred'   => $m->is_starred,
                'source'       => $m->source,
                'received'     => $m->created_at->diffForHumans(),
            ])->all();
    }

    private function toolListAppointments(array $input, $tenant): array
    {
        $query = Appointment::where('tenant_id', $tenant->id)->with('property');
        if (!empty($input['status'])) $query->where('status', $input['status']);
        if (!empty($input['date']))   $query->whereDate('appointment_date', $input['date']);
        $limit = min((int) ($input['limit'] ?? 10), 25);

        return $query->orderBy('appointment_date')->orderBy('appointment_time')->limit($limit)->get()
            ->map(fn($a) => [
                'id'               => $a->id,
                'visitor_name'     => $a->visitor_name,
                'visitor_email'    => $a->visitor_email,
                'appointment_type' => $a->appointment_type,
                'appointment_date' => $a->appointment_date->toDateString(),
                'appointment_time' => Carbon::parse($a->appointment_time)->format('g:i A'),
                'status'           => $a->status,
                'property'         => $a->property?->address_street,
                'notes'            => $a->notes,
            ])->all();
    }

    private function toolListStaff($tenant): array
    {
        return StaffMember::where('tenant_id', $tenant->id)->orderBy('sort_order')->get()
            ->map(fn($s) => [
                'id'                   => $s->id,
                'name'                 => $s->name,
                'role'                 => $s->role,
                'email'                => $s->email,
                'status'               => $s->status,
                'accepts_appointments' => $s->accepts_appointments,
            ])->all();
    }

    // ─── WRITE tools ─────────────────────────────────────────────────────────

    private function toolUpdateProperty(array $input, $tenant): array
    {
        $prop = Property::where('tenant_id', $tenant->id)->find((int) ($input['id'] ?? 0));
        if (!$prop) return ['error' => 'Property not found.'];

        $allowed = ['listing_status', 'price', 'description', 'is_featured'];
        $fields  = array_intersect_key($input['fields'] ?? [], array_flip($allowed));
        if (empty($fields)) return ['error' => 'No valid fields to update.'];

        $validStatuses = ['active', 'pending', 'sold', 'featured', 'withdrawn'];
        if (isset($fields['listing_status']) && !in_array($fields['listing_status'], $validStatuses)) {
            return ['error' => 'Invalid listing_status. Must be: ' . implode(', ', $validStatuses)];
        }

        $prop->update($fields);
        $changes = collect($fields)->map(fn($v, $k) => "{$k} → {$v}")->implode(', ');

        return [
            '_audit'        => "✓ Updated property #{$prop->id} ({$prop->title}): {$changes}",
            'success'       => true,
            'property_id'   => $prop->id,
            'updated_fields' => $fields,
        ];
    }

    private function toolUpdateMessage(array $input, $tenant): array
    {
        $msg = Message::where('tenant_id', $tenant->id)->find((int) ($input['id'] ?? 0));
        if (!$msg) return ['error' => 'Message not found.'];

        $action = $input['action'] ?? '';
        $valid  = ['read', 'unread', 'starred', 'unstarred', 'archived', 'replied', 'spam'];
        if (!in_array($action, $valid)) {
            return ['error' => 'Invalid action. Must be: ' . implode(', ', $valid)];
        }

        match ($action) {
            'read'      => $msg->update(['is_read' => true,  'status' => $msg->status === 'new' ? 'read' : $msg->status]),
            'unread'    => $msg->update(['is_read' => false]),
            'starred'   => $msg->update(['is_starred' => true]),
            'unstarred' => $msg->update(['is_starred' => false]),
            'archived'  => $msg->update(['status' => 'archived']),
            'replied'   => $msg->update(['status' => 'replied']),
            'spam'      => $msg->update(['status' => 'spam']),
            default     => null,
        };

        return [
            '_audit'     => "✓ Message from {$msg->sender_name}: marked as {$action}.",
            'success'    => true,
            'message_id' => $msg->id,
            'action'     => $action,
        ];
    }

    private function toolUpdateAppointment(array $input, $tenant): array
    {
        $appt = Appointment::where('tenant_id', $tenant->id)->find((int) ($input['id'] ?? 0));
        if (!$appt) return ['error' => 'Appointment not found.'];

        $newStatus = $input['status'] ?? '';
        $valid     = ['confirmed', 'cancelled', 'completed'];
        if (!in_array($newStatus, $valid)) {
            return ['error' => 'Invalid status. Must be: confirmed, cancelled, or completed.'];
        }

        $appt->update(['status' => $newStatus]);
        $date = $appt->appointment_date->format('M j');
        $time = Carbon::parse($appt->appointment_time)->format('g:i A');

        return [
            '_audit'         => "✓ Appointment #{$appt->id} ({$appt->visitor_name}, {$date} {$time}): marked as {$newStatus}.",
            'success'        => true,
            'appointment_id' => $appt->id,
            'new_status'     => $newStatus,
        ];
    }

    private function toolCreateAppointment(array $input, $tenant): array
    {
        $required = ['visitor_name', 'visitor_email', 'appointment_type', 'appointment_date', 'appointment_time'];
        foreach ($required as $field) {
            if (empty($input[$field])) return ['error' => "Missing required field: {$field}"];
        }

        $validTypes = ['showing', 'consultation', 'follow_up', 'other'];
        if (!in_array($input['appointment_type'], $validTypes)) {
            return ['error' => 'Invalid appointment_type. Must be: ' . implode(', ', $validTypes)];
        }

        try {
            $date = Carbon::parse($input['appointment_date'])->toDateString();
        } catch (\Exception $e) {
            return ['error' => 'Invalid date format. Use YYYY-MM-DD.'];
        }

        $propertyId = $staffId = null;
        if (!empty($input['property_id'])) {
            $prop = Property::where('tenant_id', $tenant->id)->find((int) $input['property_id']);
            if ($prop) {
                $propertyId = $prop->id;
                $staffId    = $prop->staff_member_id;
            }
        }

        $appt = Appointment::create([
            'tenant_id'        => $tenant->id,
            'property_id'      => $propertyId,
            'staff_member_id'  => $staffId,
            'visitor_name'     => $input['visitor_name'],
            'visitor_email'    => $input['visitor_email'],
            'visitor_phone'    => $input['visitor_phone'] ?? null,
            'appointment_type' => $input['appointment_type'],
            'appointment_date' => $date,
            'appointment_time' => $input['appointment_time'],
            'status'           => 'pending',
            'notes'            => $input['notes'] ?? null,
            'source'           => 'admin_chat',
        ]);

        return [
            '_audit'         => "✓ Created appointment for {$appt->visitor_name} on {$date}.",
            'success'        => true,
            'appointment_id' => $appt->id,
        ];
    }

    private function toolSendEmail(array $input, $tenant): array
    {
        $to      = trim($input['to_email'] ?? '');
        $toName  = $input['to_name'] ?? null;
        $subject = trim($input['subject'] ?? '');
        $body    = trim($input['body'] ?? '');
        $replyTo = $input['reply_to'] ?? null;

        if (!$to || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'Invalid or missing recipient email address.'];
        }
        if (empty($subject)) return ['error' => 'Subject is required.'];
        if (empty($body))    return ['error' => 'Body is required.'];

        // Default reply-to = SMTP from address
        if (!$replyTo) {
            $smtp    = Integration::where('tenant_id', $tenant->id)->where('integration_type', 'smtp')->where('is_active', true)->first();
            $replyTo = $smtp?->config['smtp_from_email'] ?? null;
        }

        $ok = TenantMailer::send($tenant->id, $to, $subject, $body, 'tenant', $toName ?: null, $replyTo);

        if (!$ok) {
            return ['error' => 'Failed to send email. Please check your SMTP settings in Settings → Email.'];
        }

        $label = $toName ? "{$toName} <{$to}>" : $to;
        return [
            '_audit'  => "✓ Email sent to {$label}: \"{$subject}\"",
            'success' => true,
            'to'      => $to,
            'subject' => $subject,
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    // ─── AI config helper ────────────────────────────────────────────────────

    private function resolveAiConfig($tenant): array
    {
        $integration = Integration::where('tenant_id', $tenant->id)
            ->where('integration_type', 'ai_provider')
            ->first();

        $cfg       = $integration?->config ?? [];
        $preferred = $cfg['preferred'] ?? 'anthropic';
        $key       = $preferred === 'openai'
            ? ($cfg['openai_key'] ?? null)
            : ($cfg['anthropic_key'] ?? null);
        $model     = $preferred === 'openai'
            ? ($cfg['openai_model'] ?? 'gpt-4o-mini')
            : ($cfg['anthropic_model'] ?? 'claude-haiku-4-5-20251001');

        return compact('preferred', 'key', 'model', 'integration');
    }

    private function buildMotd($tenant): string
    {
        $today = Carbon::today();

        $unreadCount  = Message::where('tenant_id', $tenant->id)->where('is_read', false)->count();
        $recentNames  = Message::where('tenant_id', $tenant->id)->where('is_read', false)
            ->orderByDesc('created_at')->limit(5)->pluck('sender_name')->filter()->all();

        $todayAppts = Appointment::where('tenant_id', $tenant->id)
            ->whereDate('appointment_date', $today)
            ->where('status', 'pending')
            ->orderBy('appointment_time')
            ->with('property')
            ->get();

        $activeCount  = Property::where('tenant_id', $tenant->id)->where('listing_status', 'active')->count();
        $pendingCount = Property::where('tenant_id', $tenant->id)->where('listing_status', 'pending')->count();
        $soldCount    = Property::where('tenant_id', $tenant->id)->where('listing_status', 'sold')->count();

        $hour    = (int) $today->format('H');
        $greeting = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
        $lines   = [];

        $lines[] = "{$greeting}! Here's your briefing for **{$today->format('l, F j')}**:";
        $lines[] = '';

        if ($unreadCount === 0) {
            $lines[] = '**Messages:** Inbox is clear — no unread messages.';
        } else {
            $nameStr = !empty($recentNames) ? ' — from: ' . implode(', ', $recentNames) : '';
            $lines[] = "**Messages:** {$unreadCount} unread{$nameStr}.";
        }

        if ($todayAppts->isEmpty()) {
            $lines[] = '**Appointments today:** None scheduled.';
        } else {
            $lines[] = '**Appointments today (' . $todayAppts->count() . ' pending):**';
            foreach ($todayAppts as $appt) {
                $time  = Carbon::parse($appt->appointment_time)->format('g:i A');
                $type  = ucwords(str_replace('_', ' ', $appt->appointment_type));
                $prop  = $appt->property ? ' — ' . $appt->property->address_street : '';
                $lines[] = "  • {$time}: {$type} with {$appt->visitor_name}{$prop}";
            }
        }

        $listingParts = ["{$activeCount} active"];
        if ($pendingCount > 0) $listingParts[] = "{$pendingCount} pending";
        if ($soldCount > 0)    $listingParts[] = "{$soldCount} sold";
        $lines[] = '**Listings:** ' . implode(', ', $listingParts) . '.';

        $lines[] = '';
        $lines[] = 'What would you like to do? You can ask about listings, messages, or appointments — and I can update them too.';

        return implode("\n", $lines);
    }

    private function buildSystemPrompt($tenant): string
    {
        $today = Carbon::today()->format('l, F j, Y');
        $name  = $tenant->name ?? 'your agency';

        return <<<PROMPT
You are an AI assistant for {$name}'s real estate admin dashboard.
Today is {$today}.

You help the admin query and manage their business data: listings, messages, appointments, and staff.

Guidelines:
- Always use tools to fetch fresh data rather than guessing or making up details.
- For write actions, confirm clearly what you did.
- If a request is ambiguous (e.g. "mark the message as read" when multiple unread messages exist), first call the relevant list tool, then ask which one.
- Keep replies concise and well-formatted. Use markdown: **bold**, bullet points, etc.
- You cannot delete properties, permanently delete messages, change billing, or change passwords.
- IDs are numeric. Always pass the correct integer ID to write tools.
PROMPT;
    }

    private function tools(): array
    {
        return [
            [
                'name'         => 'get_overview',
                'description'  => 'Get a summary of today\'s unread messages, pending appointments, and listing counts.',
                'input_schema' => ['type' => 'object', 'properties' => new \stdClass()],
            ],
            [
                'name'        => 'list_properties',
                'description' => 'List properties with optional filters.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'status' => ['type' => 'string', 'enum' => ['active', 'pending', 'sold', 'featured', 'withdrawn'], 'description' => 'Filter by listing status'],
                        'search' => ['type' => 'string', 'description' => 'Search by title, street address, or city'],
                        'limit'  => ['type' => 'integer', 'description' => 'Max results (default 10, max 25)'],
                    ],
                ],
            ],
            [
                'name'        => 'get_property',
                'description' => 'Get full details for a single property by ID.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => ['id' => ['type' => 'integer', 'description' => 'Property ID']],
                    'required'   => ['id'],
                ],
            ],
            [
                'name'        => 'list_messages',
                'description' => 'List contact messages, optionally filtered by status.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'status' => ['type' => 'string', 'enum' => ['new', 'read', 'replied', 'archived', 'spam'], 'description' => 'Filter by status'],
                        'limit'  => ['type' => 'integer', 'description' => 'Max results (default 10, max 25)'],
                    ],
                ],
            ],
            [
                'name'        => 'list_appointments',
                'description' => 'List appointments, optionally filtered by status or date.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'status' => ['type' => 'string', 'enum' => ['pending', 'confirmed', 'completed', 'cancelled'], 'description' => 'Filter by status'],
                        'date'   => ['type' => 'string', 'description' => 'Filter by date (YYYY-MM-DD)'],
                        'limit'  => ['type' => 'integer', 'description' => 'Max results (default 10, max 25)'],
                    ],
                ],
            ],
            [
                'name'         => 'list_staff',
                'description'  => 'List all staff members.',
                'input_schema' => ['type' => 'object', 'properties' => new \stdClass()],
            ],
            [
                'name'        => 'update_property',
                'description' => 'Update a property\'s listing status, price, description, or featured flag.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'id'     => ['type' => 'integer', 'description' => 'Property ID'],
                        'fields' => [
                            'type'       => 'object',
                            'description' => 'Fields to update',
                            'properties' => [
                                'listing_status' => ['type' => 'string', 'enum' => ['active', 'pending', 'sold', 'featured', 'withdrawn']],
                                'price'          => ['type' => 'number'],
                                'description'    => ['type' => 'string'],
                                'is_featured'    => ['type' => 'boolean'],
                            ],
                        ],
                    ],
                    'required' => ['id', 'fields'],
                ],
            ],
            [
                'name'        => 'update_message',
                'description' => 'Mark a message as read, unread, starred, unstarred, archived, replied, or spam.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'id'     => ['type' => 'integer', 'description' => 'Message ID'],
                        'action' => ['type' => 'string', 'enum' => ['read', 'unread', 'starred', 'unstarred', 'archived', 'replied', 'spam']],
                    ],
                    'required' => ['id', 'action'],
                ],
            ],
            [
                'name'        => 'update_appointment',
                'description' => 'Update an appointment\'s status to confirmed, cancelled, or completed.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'id'     => ['type' => 'integer', 'description' => 'Appointment ID'],
                        'status' => ['type' => 'string', 'enum' => ['confirmed', 'cancelled', 'completed']],
                    ],
                    'required' => ['id', 'status'],
                ],
            ],
            [
                'name'        => 'send_email',
                'description' => 'Send an email to any address using the configured SMTP account. Use when the admin asks to email a client, contact, or anyone else.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'to_email' => ['type' => 'string', 'description' => 'Recipient email address'],
                        'to_name'  => ['type' => 'string', 'description' => 'Recipient display name (optional)'],
                        'subject'  => ['type' => 'string', 'description' => 'Email subject line'],
                        'body'     => ['type' => 'string', 'description' => 'Email body text. Use plain paragraphs. For structured data use "Label: Value" lines (renders as a table). Separate sections with ──────── (renders as divider).'],
                        'reply_to' => ['type' => 'string', 'description' => 'Reply-to address (optional, defaults to the configured SMTP from address)'],
                    ],
                    'required' => ['to_email', 'subject', 'body'],
                ],
            ],
            [
                'name'        => 'create_appointment',
                'description' => 'Book a new appointment.',
                'input_schema' => [
                    'type'       => 'object',
                    'properties' => [
                        'visitor_name'     => ['type' => 'string'],
                        'visitor_email'    => ['type' => 'string'],
                        'visitor_phone'    => ['type' => 'string'],
                        'appointment_type' => ['type' => 'string', 'enum' => ['showing', 'consultation', 'follow_up', 'other']],
                        'appointment_date' => ['type' => 'string', 'description' => 'YYYY-MM-DD'],
                        'appointment_time' => ['type' => 'string', 'description' => 'HH:MM (24-hour)'],
                        'property_id'      => ['type' => 'integer'],
                        'notes'            => ['type' => 'string'],
                    ],
                    'required' => ['visitor_name', 'visitor_email', 'appointment_type', 'appointment_date', 'appointment_time'],
                ],
            ],
        ];
    }

    // ─── OpenAI loop ─────────────────────────────────────────────────────────

    private function runOpenAILoop(string $key, string $model, array $messages, $tenant): string
    {
        $allowedModels = ['gpt-4o', 'gpt-4o-mini'];
        if (!in_array($model, $allowedModels)) {
            $model = 'gpt-4o-mini';
        }

        $systemPrompt = $this->buildSystemPrompt($tenant);
        $auditLog     = [];
        $maxIter      = 8;

        // OpenAI: system prompt as first message
        $msgs = array_merge([['role' => 'system', 'content' => $systemPrompt]], $messages);

        for ($i = 0; $i < $maxIter; $i++) {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $key,
            ])->timeout(45)->post('https://api.openai.com/v1/chat/completions', [
                'model'       => $model,
                'max_tokens'  => 1024,
                'messages'    => $msgs,
                'tools'       => $this->buildOpenAITools(),
                'tool_choice' => 'auto',
            ]);

            if ($response->failed()) {
                Log::error('AdminChat OpenAI HTTP error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);
                return 'I had trouble reaching the AI service. Please try again.';
            }

            $data         = $response->json();
            $choice       = $data['choices'][0] ?? [];
            $finishReason = $choice['finish_reason'] ?? 'stop';
            $message      = $choice['message'] ?? [];

            if ($finishReason === 'stop') {
                $text = $message['content'] ?? 'Done.';
                if (!empty($auditLog)) {
                    $text = implode("\n", $auditLog) . "\n\n" . $text;
                }
                return $text;
            }

            if ($finishReason === 'tool_calls') {
                $toolCalls = $message['tool_calls'] ?? [];

                // Append assistant message with tool_calls
                $msgs[] = [
                    'role'       => 'assistant',
                    'content'    => $message['content'] ?? null,
                    'tool_calls' => $toolCalls,
                ];

                foreach ($toolCalls as $tc) {
                    $name  = $tc['function']['name'] ?? '';
                    $input = json_decode($tc['function']['arguments'] ?? '{}', true) ?? [];

                    [$result, $audit] = $this->executeTool($name, $input, $tenant);
                    if ($audit) $auditLog[] = $audit;

                    $msgs[] = [
                        'role'         => 'tool',
                        'tool_call_id' => $tc['id'],
                        'content'      => json_encode($result),
                    ];
                }
                continue;
            }

            // Unexpected finish
            return $message['content'] ?? "I\'m not sure how to respond to that.";
        }

        return 'I hit a processing limit. Please try rephrasing your request.';
    }

    private function buildOpenAITools(): array
    {
        return array_map(fn($tool) => [
            'type'     => 'function',
            'function' => [
                'name'        => $tool['name'],
                'description' => $tool['description'],
                'parameters'  => $tool['input_schema'],
            ],
        ], $this->tools());
    }

    private function friendlyModelName(string $model): string
    {
        return match ($model) {
            'claude-opus-4-6'            => 'Claude Opus 4.6',
            'claude-sonnet-4-6'          => 'Claude Sonnet 4.6',
            'claude-haiku-4-5-20251001'  => 'Claude Haiku 4.5',
            'gpt-4o'                     => 'GPT-4o',
            'gpt-4o-mini'                => 'GPT-4o Mini',
            default                      => $model,
        };
    }

}
