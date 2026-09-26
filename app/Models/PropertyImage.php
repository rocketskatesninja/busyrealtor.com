<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use App\Support\ImageStore;
use Illuminate\Database\Eloquent\Model;
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
            if ($image->image_url) ImageStore::delete($image->image_url);
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

    /**
     * The card-sized copy for grids, strips and cards, falling back to the full image when
     * no derivative was generated — rows predate it and a backfill can be partial.
     */
    public function getThumbPathAttribute(): ?string
    {
        return ImageStore::thumbnailOrOriginal($this->image_url);
    }
}
