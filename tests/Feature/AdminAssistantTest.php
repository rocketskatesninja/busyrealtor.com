<?php

namespace Tests\Feature;

use App\Models\ChatLog;
use App\Models\Integration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | The admin AI assistant never worked on Anthropic — which is the default provider.
 |
 | Opening a session seeds the message of the day as an *assistant* row in chat_logs, and the
 | whole log is replayed to the provider verbatim. Anthropic refuses a conversation that opens
 | on an assistant turn, so the call came back 400, the catch turned it into "I had trouble
 | reaching the AI service. Please try again." and that is what the admin saw every single
 | time. OpenAI escaped it only because its path prepends a system message.
 |
 | The same fault had a second route in: the twenty-message window can start mid-conversation
 | on a reply, which breaks a long session even with no MOTD. Both are covered here.
 */
class AdminAssistantTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    private function proTenantWithAnthropic(): \App\Models\Tenant
    {
        $tenant = $this->makeTenant(['slug' => 'assist', 'plan' => 'pro']);

        Integration::create([
            'tenant_id' => $tenant->id,
            'integration_type' => 'ai_provider',
            'provider' => 'anthropic',
            'is_active' => true,
            'config' => ['preferred' => 'anthropic', 'anthropic_key' => 'sk-ant-test'],
        ]);

        return $tenant;
    }

    private function fakeAnthropic(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'stop_reason' => 'end_turn',
                'content' => [['type' => 'text', 'text' => 'You have three active listings.']],
            ], 200),
        ]);
    }

    public function test_a_session_opening_with_the_motd_still_reaches_anthropic(): void
    {
        $this->fakeAnthropic();
        $tenant = $this->proTenantWithAnthropic();
        $admin = $this->makeAdmin($tenant);

        // Exactly what opening the assistant does: the MOTD, stored as an assistant turn.
        ChatLog::create([
            'tenant_id' => $tenant->id, 'session_id' => 'admin_session_1',
            'role' => 'assistant', 'content' => 'Good morning. Here is your overview.',
        ]);

        $response = $this->actingAs($admin)->postJson("/{$tenant->slug}/admin/api/assistant", [
            'message' => 'How many listings are active?',
            'session_id' => 'admin_session_1',
        ]);

        $response->assertOk();
        $this->assertSame('You have three active listings.', $response->json('reply'));
        $this->assertStringNotContainsString('trouble reaching', (string) $response->json('reply'));

        Http::assertSent(function ($request) {
            $roles = array_column($request->data()['messages'] ?? [], 'role');

            return ($roles[0] ?? null) === 'user';
        });
    }

    public function test_a_long_session_never_opens_on_an_assistant_turn(): void
    {
        $this->fakeAnthropic();
        $tenant = $this->proTenantWithAnthropic();
        $admin = $this->makeAdmin($tenant);

        // 30 turns, so the twenty-message window lands mid-conversation. Whether it opens on
        // a reply depends on where the window falls — it must be trimmed either way.
        for ($i = 0; $i < 15; $i++) {
            ChatLog::create(['tenant_id' => $tenant->id, 'session_id' => 'admin_long', 'role' => 'user', 'content' => "q{$i}"]);
            ChatLog::create(['tenant_id' => $tenant->id, 'session_id' => 'admin_long', 'role' => 'assistant', 'content' => "a{$i}"]);
        }

        $this->actingAs($admin)->postJson("/{$tenant->slug}/admin/api/assistant", [
            'message' => 'One more question', 'session_id' => 'admin_long',
        ])->assertOk();

        Http::assertSent(function ($request) {
            $roles = array_column($request->data()['messages'] ?? [], 'role');

            return ($roles[0] ?? null) === 'user' && count($roles) <= 20;
        });
    }

    public function test_the_reply_is_recorded_so_the_next_turn_has_context(): void
    {
        $this->fakeAnthropic();
        $tenant = $this->proTenantWithAnthropic();

        // chat() refuses a session it does not already own, so seed the MOTD row the
        // assistant page would have written when the session opened.
        ChatLog::create([
            'tenant_id' => $tenant->id, 'session_id' => 'admin_recorded',
            'role' => 'assistant', 'content' => 'Good morning.',
        ]);

        $this->actingAs($this->makeAdmin($tenant))->postJson("/{$tenant->slug}/admin/api/assistant", [
            'message' => 'Hello', 'session_id' => 'admin_recorded',
        ])->assertOk();

        $roles = ChatLog::withoutGlobalScopes()->where('session_id', 'admin_recorded')->orderBy('id')->pluck('role')->all();
        $this->assertSame(['assistant', 'user', 'assistant'], $roles, 'the MOTD, the question, then the reply');
    }
}
