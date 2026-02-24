<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class StaffMember extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'name',
        'role',
        'email',
        'phone',
        'bio',
        'photo_url',
        'status',
        'sort_order',
        'display_on_homepage',
        'accepts_appointments',
    ];

    protected $casts = [
        'display_on_homepage'  => 'boolean',
        'accepts_appointments' => 'boolean',
    ];

    protected static function boot(): void
    {
        parent::boot();
        static::deleting(function (self $member): void {
            if ($member->photo_url) Storage::disk('public')->delete($member->photo_url);
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function properties(): HasMany
    {
        return $this->hasMany(Property::class);
    }

    public function appointments(): HasMany
    {
        return $this->hasMany(Appointment::class);
    }
}
