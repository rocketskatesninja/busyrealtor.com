<?php

namespace Tests\Feature;

use Tests\TestCase;

/*
 | The admin nav shows two counts, appointments and messages, on desktop and again in the
 | mobile menu. The messages badge used var(--primary); the appointments badge was hardcoded
 | — #F59E0B on desktop, bg-yellow-500 on mobile. That went unnoticed because the tenant's
 | primary colour happened to be orange, so the hardcoded amber looked like it belonged.
 | Changing the site colour to purple left those two badges behind.
 */
class BadgeColourTest extends TestCase
{
    public function test_every_notification_badge_follows_the_site_colour(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));

        $offenders = [];

        foreach (explode("\n", $layout) as $number => $line) {
            if (! str_contains($line, '$pendingAppointments }}') && ! str_contains($line, '$unreadMessages }}')) {
                continue;
            }

            if (! str_contains($line, 'var(--primary)')) {
                $offenders[] = ($number + 1).': '.trim($line);
            }
        }

        $this->assertSame([], $offenders,
            "these badges do not follow the site colour:\n  ".implode("\n  ", $offenders));
    }

    /** All four of them are still there — the guard above would also pass if they vanished. */
    public function test_there_are_still_four_notification_badges(): void
    {
        $layout = file_get_contents(resource_path('views/layouts/admin.blade.php'));

        $this->assertSame(2, substr_count($layout, '$pendingAppointments }}'), 'desktop + mobile appointment badges');
        $this->assertSame(2, substr_count($layout, '$unreadMessages }}'), 'desktop + mobile message badges');
    }
}
