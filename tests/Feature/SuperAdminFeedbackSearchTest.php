<?php

namespace Tests\Feature;

use App\Models\Feedback;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | Searching super-admin feedback threw SQLSTATE[42S22] on every query.
 |
 | The search joined `users.name`, a column dropped by split_user_name_fields. `name` survives
 | as an accessor on the model, which is why it works everywhere a PHP property is read and
 | nowhere a query is built — so the code looked right. Any `?search=` was a 500.
 */
class SuperAdminFeedbackSearchTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    public function test_feedback_can_be_searched_by_subject_message_and_submitter_name(): void
    {
        $tenant = $this->makeTenant();
        $author = $this->makeAdmin($tenant, ['first_name' => 'Marguerite', 'last_name' => 'Vance']);
        $super = $this->makeSuperAdmin();

        Feedback::create([
            'tenant_id' => $tenant->id,
            'user_id' => $author->id,
            'subject' => 'Map pin drifts',
            'message' => 'The marker sits in the wrong street.',
            'status' => 'new',
        ]);

        foreach (['Map pin', 'wrong street', 'Marguerite', 'Vance'] as $term) {
            $this->actingAs($super)
                ->get('/super-admin/feedback?search='.urlencode($term))
                ->assertOk()
                ->assertSee('Map pin drifts', false);
        }
    }

    public function test_a_search_that_matches_nothing_is_still_a_200(): void
    {
        $tenant = $this->makeTenant();
        $author = $this->makeAdmin($tenant);

        // feedback.user_id is NOT NULL with no default — every submission has an author.
        Feedback::create([
            'tenant_id' => $tenant->id, 'user_id' => $author->id,
            'subject' => 'Kitchen photo rotates', 'message' => 'Sideways after upload.', 'status' => 'new',
        ]);

        $this->actingAs($this->makeSuperAdmin())
            ->get('/super-admin/feedback?search=nothingmatchesthis')
            ->assertOk()
            ->assertDontSee('Kitchen photo rotates', false);
    }
}
