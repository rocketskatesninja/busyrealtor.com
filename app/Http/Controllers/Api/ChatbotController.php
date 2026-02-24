<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatLog;
use App\Models\Integration;
use App\Models\SiteSettings;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ChatbotController extends Controller
{
    public function chat($account, Request $request)
    {
        $request->validate([
            'message'    => 'required|string|max:2000',
            'session_id' => 'nullable|string',
        ]);

        $tenant   = app('tenant');
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
        $featuredProps = Property::where('listing_status', 'active')->limit(5)->get()
            ->map(fn($p) => "{$p->title} - {$p->address} - \${$p->price}")->implode("\n");

        $sysPrompt = "You are a helpful real estate assistant for {$tenant->name}. ";
        if ($settings?->chatbot_realtor_bio) {
            $sysPrompt .= "About the realtor: {$settings->chatbot_realtor_bio}. ";
        }
        if ($settings) {
            $sysPrompt .= "Contact: {$settings->contact_email}, {$settings->contact_phone}. ";
        }
        if ($featuredProps) {
            $sysPrompt .= "Active listings:\n{$featuredProps}\n";
        }
        $sysPrompt .= "Be concise and helpful.";

        // Build message history from stored role/content rows
        $messages = $history->map(fn($log) => [
            'role'    => $log->role,
            'content' => $log->content,
        ])->all();
        $messages[] = ['role' => 'user', 'content' => $request->message];

        $config = $aiInteg->config ?? [];
        $reply  = '';

        try {
            if ($aiInteg->provider === 'anthropic') {
                $model = $config['model'] ?? 'claude-haiku-4-5-20251001';
                $resp  = Http::withHeaders([
                    'x-api-key'         => $aiInteg->api_key,
                    'anthropic-version' => '2023-06-01',
                ])->post('https://api.anthropic.com/v1/messages', [
                    'model'      => $model,
                    'max_tokens' => 500,
                    'system'     => $sysPrompt,
                    'messages'   => $messages,
                ]);
                $reply = $resp->json('content.0.text') ?? 'Sorry, I could not respond.';
            } else {
                // OpenAI (default)
                $model = $config['model'] ?? 'gpt-4o-mini';
                $resp  = Http::withToken($aiInteg->api_key)
                    ->post('https://api.openai.com/v1/chat/completions', [
                        'model'      => $model,
                        'max_tokens' => 500,
                        'messages'   => array_merge(
                            [['role' => 'system', 'content' => $sysPrompt]],
                            $messages
                        ),
                    ]);
                $reply = $resp->json('choices.0.message.content') ?? 'Sorry, I could not respond.';
            }

            if ($resp->failed()) {
                \Log::error('Chatbot API error', ['status' => $resp->status(), 'body' => $resp->body()]);
                $reply = 'Sorry, I am having trouble right now. Please call or email us directly.';
            }
        } catch (\Exception $e) {
            \Log::error('Chatbot exception', ['error' => $e->getMessage()]);
            $reply = 'Sorry, I am having trouble right now. Please call or email us directly.';
        }

        // Store user message and assistant reply as separate rows
        ChatLog::create(['tenant_id' => $tenant->id, 'session_id' => $sessionId, 'role' => 'user',      'content' => $request->message]);
        ChatLog::create(['tenant_id' => $tenant->id, 'session_id' => $sessionId, 'role' => 'assistant', 'content' => $reply]);

        return response()->json(['reply' => $reply, 'session_id' => $sessionId]);
    }
}
