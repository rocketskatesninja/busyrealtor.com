<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\MakesTenants;
use Tests\TestCase;

/*
 | 305 labels across the views, 9 of which had a for=. 48 more wrapped their control and were
 | implicitly fine; the remaining 194 sat immediately before a control with nothing tying the
 | two together, so a screen reader announced an unlabelled field and clicking the label text
 | did not focus the input.
 |
 | 131 of those could be associated without guessing. These tests check the property that
 | matters and that a static pass cannot see: on a rendered page, every for= resolves to
 | exactly one element. The filter partial is included twice per page, which duplicated every
 | id the first time this ran — caught here, not in review.
 */
class LabelAssociationTest extends TestCase
{
    use MakesTenants;
    use RefreshDatabase;

    /** @return array{ids: array<string,int>, fors: array<int,string>} */
    private function parse(string $html): array
    {
        preg_match_all('/\bid="([^"]+)"/', $html, $idMatches);
        preg_match_all('/<label[^>]*\bfor="([^"]+)"/', $idMatches ? $html : $html, $forMatches);

        return [
            'ids' => array_count_values($idMatches[1]),
            'fors' => array_unique($forMatches[1]),
        ];
    }

    private function assertLabelsResolve(string $html, string $where): void
    {
        ['ids' => $ids, 'fors' => $fors] = $this->parse($html);

        $this->assertNotEmpty($fors, "{$where} has no associated labels at all");

        foreach ($fors as $for) {
            $this->assertArrayHasKey($for, $ids, "{$where}: for=\"{$for}\" points at no element");
            $this->assertSame(1, $ids[$for],
                "{$where}: for=\"{$for}\" points at {$ids[$for]} elements, so it resolves to whichever comes first");
        }
    }

    private function assertNoDuplicateFieldIds(string $html, string $where): void
    {
        ['ids' => $ids] = $this->parse($html);

        $duplicated = array_keys(array_filter(
            $ids,
            fn ($count, $id) => $count > 1 && str_starts_with($id, 'f-'),
            ARRAY_FILTER_USE_BOTH
        ));

        $this->assertSame([], $duplicated, "{$where} renders duplicate field ids: ".implode(', ', $duplicated));
    }

    /**
     * Passes against the previous views too: the filter partial already had a couple of
     * associated fields among the original nine, so this asserts an invariant rather than
     * demonstrating the change. It earns its place by failing if a future edit points a
     * label at something that is not there.
     */
    public function test_public_pages_have_labels_that_resolve(): void
    {
        $tenant = $this->makeTenant();
        $this->makeProperty($tenant);

        foreach (['', '/gallery', '/map', '/contact'] as $page) {
            $html = $this->get("/{$tenant->slug}{$page}")->assertOk()->content();
            $this->assertLabelsResolve($html, "/{$tenant->slug}{$page}");
            $this->assertNoDuplicateFieldIds($html, "/{$tenant->slug}{$page}");
        }
    }

    /**
     * The gallery and the map include the filter partial twice — once in the sidebar, once in
     * a mobile drawer — so this is the case that actually broke.
     */
    /** Also an invariant now — but it is the one that caught the duplication. */
    public function test_a_partial_included_twice_does_not_duplicate_its_field_ids(): void
    {
        $tenant = $this->makeTenant();
        $this->makeProperty($tenant);

        foreach (['/gallery', '/map'] as $page) {
            $html = $this->get("/{$tenant->slug}{$page}")->assertOk()->content();

            $this->assertGreaterThanOrEqual(2, substr_count($html, 'name="price_min"'),
                "{$page} should render the filter fields more than once");
            $this->assertNoDuplicateFieldIds($html, $page);
            $this->assertLabelsResolve($html, $page);
        }
    }

    public function test_the_login_form_labels_resolve(): void
    {
        $html = $this->get('/login')->assertOk()->content();

        $this->assertStringContainsString('for="f-email"', $html);
        $this->assertStringContainsString('id="f-email"', $html);
        $this->assertLabelsResolve($html, '/login');
    }

    public function test_admin_settings_labels_resolve(): void
    {
        $tenant = $this->makeTenant();
        $admin = $this->makeAdmin($tenant);

        $html = $this->actingAs($admin)->get("/{$tenant->slug}/admin/settings")->assertOk()->content();

        $this->assertLabelsResolve($html, 'admin settings');
        $this->assertNoDuplicateFieldIds($html, 'admin settings');
    }

    public function test_super_admin_settings_labels_resolve(): void
    {
        $html = $this->actingAs($this->makeSuperAdmin())
            ->get('/super-admin/settings')->assertOk()->content();

        $this->assertLabelsResolve($html, 'super-admin settings');
        $this->assertNoDuplicateFieldIds($html, 'super-admin settings');
    }
}
