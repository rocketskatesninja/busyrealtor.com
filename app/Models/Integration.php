<?php

namespace App\Models;

use App\Models\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;

class Integration extends Model
{
    use BelongsToTenant;

    protected $fillable = [
        'tenant_id',
        'integration_type',
        'api_key',
        'provider',
        'config',
        'webhook_token',
        'is_active',
    ];

    protected $casts = [
        'config'    => 'array',
        'is_active' => 'boolean',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Decrypt the stored API key (stored with Laravel encrypt()).
     */
    public function decryptKey(): ?string
    {
        if (empty($this->api_key)) {
            return null;
        }
        try {
            return Crypt::decryptString($this->api_key);
        } catch (\Throwable $e) {
            return $this->api_key;
        }
    }
}
