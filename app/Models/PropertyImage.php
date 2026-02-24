<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyImage extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'property_id',
        'tenant_id',
        'image_url',
        'is_primary',
        'sort_order',
        'label',
        'caption',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::deleting(function (self $image): void {
            if ($image->image_url) Storage::disk('public')->delete($image->image_url);
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function getImagePathAttribute(): ?string
    {
        return $this->image_url;
    }
}
