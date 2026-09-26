<?php

namespace App\Console\Commands;

use App\Models\PropertyView;
use Illuminate\Console\Command;

class PrunePageViews extends Command
{
    protected $signature = 'app:prune-page-views {--days=90 : Keep pageviews newer than this}';

    protected $description = 'Remove property pageview rows older than the retention window';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $cutoff = now()->subDays($days);

        // Delete in batches: this table gets a row per public pageview, so the first run on
        // a busy tenant could otherwise be one enormous statement holding locks.
        $removed = 0;
        do {
            $batch = PropertyView::where('viewed_at', '<', $cutoff)->limit(1000)->delete();
            $removed += $batch;
        } while ($batch > 0);

        $this->info("Removed {$removed} pageview rows older than {$days} days (before {$cutoff->toDateString()}).");

        return self::SUCCESS;
    }
}
