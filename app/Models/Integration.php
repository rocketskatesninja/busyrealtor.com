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
        'config'        => 'array',
        'is_active'     => 'boolean',
        'api_key'       => 'encrypted',
        'webhook_token' => 'encrypted',
    ];

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Return the API key (decrypted automatically via 'encrypted' cast).
     */
    public function decryptKey(): ?string
    {
        return $this->api_key ?: null;
    }
}
