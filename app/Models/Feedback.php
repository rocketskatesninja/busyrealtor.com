<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Feedback extends Model
{
    use BelongsToTenant;

    protected $table = 'feedback';

    protected $fillable = [
        'tenant_id', 'user_id', 'subject', 'message', 'screenshot_path', 'status',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Always returns an array of paths.
     * Handles legacy single-path strings and new JSON arrays.
     */
    public function getScreenshotPathAttribute(?string $value): array
    {
        if (!$value) return [];
        $decoded = json_decode($value, true);
        return is_array($decoded) ? $decoded : [$value];
    }

    public function setScreenshotPathAttribute(array $paths): void
    {
        $this->attributes['screenshot_path'] = json_encode(array_values($paths));
    }

    public function hasScreenshot(): bool
    {
        return count($this->screenshot_path) > 0;
    }

    public function screenshots(): array
    {
        return array_filter($this->screenshot_path, fn ($p) => Storage::disk('local')->exists($p));
    }

    protected static function booted(): void
    {
        static::deleting(function (Feedback $feedback) {
            foreach ($feedback->screenshot_path as $path) {
                Storage::disk('local')->delete($path);
            }
        });
    }
}
