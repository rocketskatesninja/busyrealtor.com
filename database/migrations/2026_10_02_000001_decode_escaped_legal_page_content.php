<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Legal page bodies were stored already HTML-escaped, so a policy reading
 * ("we", "us", or "our") came out of the database as (&quot;we&quot;, ...). The view is
 * right to escape with e() before nl2br, which turned those entities into &amp;quot;
 * and printed them to the visitor literally.
 *
 * The admin editor renders the body through {{ }} into a textarea, so an entity
 * round-trips unchanged rather than escalating -- the content has been sitting at this
 * one level of escaping, not drifting deeper. That makes a single decode pass the whole
 * fix, and makes it safe: there are no &amp; sequences to decode twice.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('legal_pages')->orderBy('id')->chunkById(100, function ($pages) {
            foreach ($pages as $page) {
                $content = (string) $page->content;
                $decoded = html_entity_decode($content, ENT_QUOTES | ENT_HTML5, 'UTF-8');

                if ($decoded === $content) {
                    continue;
                }

                DB::table('legal_pages')->where('id', $page->id)->update(['content' => $decoded]);
            }
        });
    }

    /**
     * Deliberately irreversible. Re-encoding would escape every quote in the body,
     * including the ones that were always literal, so rolling back would corrupt more
     * than it restored. The pre-migration rows are in ~/db-backups/legal_pages-*.json.
     */
    public function down(): void
    {
        //
    }
};
