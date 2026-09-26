<?php

namespace Tests\Feature;

use App\Models\ChatLog;
use App\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | The public chatbot used to fail intermittently, and unreproducibly.
 |
 | It replayed the conversation ordered by created_at. Both rows of a turn — the visitor's
 | message and the reply — are written in the same request and normally land in the same
 | second, so their relative order was undefined. When the pair came back swapped, the
 | messages array stopped alternating, the provider rejected it, and the visitor was told
 | "Sorry, I am having trouble right now" for no visible reason.
 |
 | The fix is to order by id. To prove the code actually uses id rather than happening to
 | agree with it, these fixtures store created_at deliberately out of order: the assistant
 | row is stamped *earlier* than the user message it answered. Ordered by created_at the
 | history would open with an assistant turn; ordered by id it opens with the visitor.
 */
class ChatHistoryOrderTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    public function test_history_is_replayed_in_id_order_not_timestamp_order(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'content' => [['type' => 'text', 'text' => 'Certainly, I can help.']],
            ], 200),
        ]);

        $tenant = $this->makeTenant(['slug' => 'chatco', 'plan' => 'pro'], ['chatbot_enabled' => true]);

        Integration::create([
            'tenant_id' => $tenant->id,
            'integration_type' => 'ai_provider',
            'provider' => 'anthropic',
            'is_active' => true,
            'config' => ['preferred' => 'anthropic', 'anthropic_key' => 'sk-ant-test'],
        ]);

        $session = 'session-abc-123';

        // Turn one, with timestamps in the wrong order relative to insertion.
        $user = ChatLog::create([
            'tenant_id' => $tenant->id, 'session_id' => $session,
            'role' => 'user', 'content' => 'First question',
        ]);
        $assistant = ChatLog::create([
            'tenant_id' => $tenant->id, 'session_id' => $session,
            'role' => 'assistant', 'content' => 'First answer',
        ]);

        $user->forceFill(['created_at' => now()->subSeconds(5)])->saveQuietly();
        $assistant->forceFill(['created_at' => now()->subSeconds(9)])->saveQuietly();

        $this->postJson("/{$tenant->slug}/api/chatbot", [
            'message' => 'Second question',
            'session_id' => $session,
        ])->assertOk();

        Http::assertSent(function ($request) {
            $roles = array_column($request->data()['messages'] ?? [], 'role');

            // Opens with the visitor, and alternates from there. Ordered by created_at this
            // would have been ['assistant', 'user', 'user'].
            return $roles === ['user', 'assistant', 'user'];
        });
    }
}
