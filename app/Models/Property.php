<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use BelongsToTenant;

    protected static function boot(): void
    {
        parent::boot();
        static::deleting(function (self $property): void {
            $property->images()->each(fn($img) => $img->delete());
        });
    }

    protected $fillable = [
        'tenant_id',
        'title',
        'half_baths',
        'staff_member_id',
        'listing_status',
        'property_type',
        'price',
        'address_street',
        'address_city',
        'address_state',
        'address_zip',
        'latitude',
        'longitude',
        'bedrooms',
        'bathrooms',
        'square_feet',
        'lot_size',
        'year_built',
        'stories',
        'condition',
        'school_district',
        'hoa_fee',
        'garage',
        'has_pool',
        'has_basement',
        'has_fireplace',
        'near_school',
        'near_hospital',
        'near_shopping',
        'near_transit',
        'description',
        'mls_number',
        'virtual_tour_url',
        'video_tour_url',
        'view_count',
        'is_featured',
        'amenities',
    ];

    protected $casts = [
        'price'        => 'decimal:2',
        'has_pool'     => 'boolean',
        'has_basement' => 'boolean',
        'has_fireplace'=> 'boolean',
        'near_school'  => 'boolean',
        'near_hospital'=> 'boolean',
        'near_shopping'=> 'boolean',
        'near_transit' => 'boolean',
        'is_featured'  => 'boolean',
        'amenities'    => 'array',
    ];

    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order');
    }

    public function propertyViews(): HasMany
    {
        return $this->hasMany(PropertyView::class);
    }

    public function staffMember(): BelongsTo
    {
        return $this->belongsTo(StaffMember::class);
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get the primary image or first image.
     */
    public function getPrimaryImageAttribute(): ?PropertyImage
    {
        return $this->images->firstWhere('is_primary', true)
            ?? $this->images->first();
    }
    // Column alias accessors for view compatibility
    public function getAddressAttribute(): ?string { return $this->address_street; }
    public function getCityAttribute(): ?string { return $this->address_city; }
    public function getStateAttribute(): ?string { return $this->address_state; }
    public function getZipAttribute(): ?string { return $this->address_zip; }
    public function getSqftAttribute(): ?int { return $this->square_feet; }
    public function getHoaFeesAttribute(): ?float { return $this->hoa_fee; }
    public function getGarageSpacesAttribute(): mixed { return $this->garage; }


}
