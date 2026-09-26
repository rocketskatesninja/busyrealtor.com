<?php

namespace App\Console\Commands;

use App\Models\PropertyImage;
use App\Support\ImageStore;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateThumbnails extends Command
{
    protected $signature = 'images:thumbnails
                            {--force : Rewrite derivatives that already exist}
                            {--tenant= : Limit to one tenant id}';

    protected $description = 'Generate the card-sized derivative for property photos uploaded before they existed';

    public function handle(): int
    {
        $query = PropertyImage::withoutGlobalScopes()->whereNotNull('image_url');

        if ($tenant = $this->option('tenant')) {
            $query->where('tenant_id', (int) $tenant);
        }

        $total = $query->count();
        $this->info("Property photos to consider: {$total}");

        $written = $skipped = $missing = 0;
        $bytesBefore = $bytesAfter = 0;
        $disk = Storage::disk('public');
        $bar = $this->output->createProgressBar($total);

        // chunkById, not chunk: writing files does not move rows, but a concurrent upload
        // would shift an offset-paged query underneath us.
        $query->chunkById(100, function ($images) use (&$written, &$skipped, &$missing, &$bytesBefore, &$bytesAfter, $disk, $bar) {
            foreach ($images as $image) {
                $bar->advance();

                if (! $disk->exists($image->image_url)) {
                    $missing++;
                    continue;
                }

                $thumbnail = ImageStore::thumbnailFor($image->image_url);

                if ($disk->exists($thumbnail) && ! $this->option('force')) {
                    $skipped++;
                    continue;
                }

                $bytesBefore += $disk->size($image->image_url);

                if (ImageStore::writeThumbnail($image->image_url)) {
                    $bytesAfter += $disk->size($thumbnail);
                    $written++;
                }
            }
        });

        $bar->finish();
        $this->newLine(2);

        $this->line("  written: {$written}");
        $this->line("  already present: {$skipped}");
        if ($missing) {
            $this->warn("  rows whose file is gone: {$missing}");
        }

        if ($written) {
            $this->line(sprintf(
                '  those %d photos: %s in full size, %s as derivatives (%d%% smaller)',
                $written,
                $this->human($bytesBefore),
                $this->human($bytesAfter),
                $bytesBefore > 0 ? round(100 - ($bytesAfter / $bytesBefore * 100)) : 0
            ));
        }

        return self::SUCCESS;
    }

    private function human(int $bytes): string
    {
        return $bytes >= 1048576
            ? round($bytes / 1048576, 2).' MB'
            : round($bytes / 1024).' KB';
    }
}
