<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\AdminChatController;
use App\Http\Controllers\Api\ChatbotController;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | appointment_time arrives from a language model and went straight into a TIME column.
 |
 | "2pm" became "2pm:00" and "14:00:00" became "14:00:00:00"; MySQL in strict mode threw, the
 | exception was swallowed, and the visitor was told only that the request could not be
 | submitted — with nothing to suggest why or how to fix it.
 |
 | Both paths are exercised through reflection because the logic is private and there is no
 | seam to reach it through: the public chatbot's path needs a full provider round-trip, and
 | the assistant's needs a tool-call round-trip. The behaviour is what matters here, not the
 | route it is reached by.
 */
class ModelSuppliedTimeTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    /** @dataProvider times */
    public function test_the_chatbot_only_accepts_a_real_24_hour_time(string $input, ?string $expected): void
    {
        $method = new ReflectionMethod(ChatbotController::class, 'normaliseTime');
        $method->setAccessible(true);

        $this->assertSame($expected, $method->invoke(null, $input));
    }

    public static function times(): array
    {
        return [
            'hh:mm' => ['14:00', '14:00:00'],
            'leading zero' => ['09:05', '09:05:00'],
            'already has seconds' => ['14:00:00', '14:00:00'],
            'spoken form' => ['2pm', null],
            'hour out of range' => ['25:00', null],
            'minute out of range' => ['14:70', null],
            'single digits' => ['9:5', null],
            'empty' => ['', null],
            'prose' => ['sometime after lunch', null],
        ];
    }

    public function test_the_assistant_tool_asks_again_instead_of_throwing(): void
    {
        $tenant = $this->makeTenant();

        $method = new ReflectionMethod(AdminChatController::class, 'toolCreateAppointment');
        $method->setAccessible(true);

        $result = $method->invoke(app(AdminChatController::class), [
            'visitor_name' => 'Vic Visitor',
            'visitor_email' => 'vic@example.test',
            'appointment_type' => 'showing',
            'appointment_date' => now()->addWeek()->toDateString(),
            'appointment_time' => '2pm',
        ], $tenant);

        // An error the model can act on, rather than a swallowed database exception.
        $this->assertArrayHasKey('error', $result);
        $this->assertStringContainsString('HH:MM', $result['error']);
        $this->assertSame(0, \App\Models\Appointment::withoutGlobalScopes()->count());
    }

    public function test_the_assistant_tool_still_books_a_well_formed_time(): void
    {
        $tenant = $this->makeTenant();

        $method = new ReflectionMethod(AdminChatController::class, 'toolCreateAppointment');
        $method->setAccessible(true);

        $result = $method->invoke(app(AdminChatController::class), [
            'visitor_name' => 'Vic Visitor',
            'visitor_email' => 'vic@example.test',
            'appointment_type' => 'showing',
            'appointment_date' => now()->addWeek()->toDateString(),
            'appointment_time' => '14:30',
        ], $tenant);

        $this->assertArrayNotHasKey('error', $result);

        $appointment = \App\Models\Appointment::withoutGlobalScopes()->firstOrFail();
        $this->assertSame('14:30:00', (string) $appointment->getRawOriginal('appointment_time'));
    }
}
