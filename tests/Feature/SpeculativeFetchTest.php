<?php

namespace Tests\Feature;

use App\Models\Feedback;
use App\Models\Message;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | Two screens mark a record as seen while rendering it: opening a message marks it read,
 | opening a feedback item marks it reviewed. That is what people want from those screens,
 | but it puts a write behind a GET, and browsers fetch links nobody clicked — prefetch or
 | prerender on hover, link previews on paste, extensions scanning a page. Each of those
 | quietly consumed the unread state of something the admin had not looked at, which is the
 | one piece of state those screens exist to show.
 |
 | The page still renders identically; only the write is skipped.
 */
class SpeculativeFetchTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    private function message(int $tenantId): Message
    {
        return Message::create([
            'tenant_id' => $tenantId,
            'source' => 'contact_form',
            'sender_name' => 'Dana Buyer',
            'sender_email' => 'dana@example.test',
            'message' => 'Is the cottage still available?',
            'status' => 'new',
            'is_read' => false,
        ]);
    }

    public function test_opening_a_message_still_marks_it_read(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);
        $message = $this->message($tenant->id);

        $this->actingAs($admin)
            ->get("/{$tenant->slug}/admin/messages?view={$message->id}")
            ->assertOk()
            ->assertSee('Dana Buyer');

        $this->assertTrue($message->fresh()->is_read);
    }

    public static function speculativeHeaders(): array
    {
        return [
            'Chrome prefetch' => ['Sec-Purpose', 'prefetch'],
            'Chrome prerender' => ['Sec-Purpose', 'prefetch;prerender'],
            'older spelling' => ['Purpose', 'prefetch'],
            'Firefox' => ['X-Moz', 'prefetch'],
        ];
    }

    #[DataProvider('speculativeHeaders')]
    public function test_a_prefetched_message_stays_unread(string $header, string $value): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);
        $message = $this->message($tenant->id);

        $response = $this->actingAs($admin)
            ->withHeaders([$header => $value])
            ->get("/{$tenant->slug}/admin/messages?view={$message->id}");

        $response->assertOk();
        $response->assertSee('Dana Buyer');
        $this->assertFalse($message->fresh()->is_read, "a {$header}: {$value} fetch marked the message read");
    }

    public function test_opening_a_feedback_item_still_marks_it_reviewed(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);
        $feedback = Feedback::create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'subject' => 'Gallery filter',
            'message' => 'The gallery filter is slow.',
            'status' => 'new',
        ]);

        $this->actingAs($this->makeSuperAdmin())
            ->get("/super-admin/feedback?id={$feedback->id}")
            ->assertOk();

        $this->assertSame('reviewed', $feedback->fresh()->status);
    }

    public function test_a_prefetched_feedback_item_stays_new(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);
        $feedback = Feedback::create([
            'tenant_id' => $tenant->id,
            'user_id' => $admin->id,
            'subject' => 'Gallery filter',
            'message' => 'The gallery filter is slow.',
            'status' => 'new',
        ]);

        $this->actingAs($this->makeSuperAdmin())
            ->withHeaders(['Sec-Purpose' => 'prefetch'])
            ->get("/super-admin/feedback?id={$feedback->id}")
            ->assertOk();

        $this->assertSame('new', $feedback->fresh()->status, 'a prefetch marked the feedback reviewed');
    }
}
