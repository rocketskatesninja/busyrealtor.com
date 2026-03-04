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

    public function hasScreenshot(): bool
    {
        return $this->screenshot_path && Storage::disk('local')->exists($this->screenshot_path);
    }

    protected static function booted(): void
    {
        static::deleting(function (Feedback $feedback) {
            if ($feedback->screenshot_path) {
                Storage::disk('local')->delete($feedback->screenshot_path);
            }
        });
    }
}
