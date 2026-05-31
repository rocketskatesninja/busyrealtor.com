<?php

namespace App\Services;

use App\Models\Tenant;

class AiProviderService
{
    public const DEFAULT_OPENAI_MODEL    = 'gpt-4o-mini';
    public const DEFAULT_ANTHROPIC_MODEL = 'claude-haiku-4-5-20251001';

    /**
     * Resolve the AI provider config for a tenant.
     *
     * Returns an associative array:
     *  - integration: ?Integration  the underlying Integration row (if any)
     *  - preferred:   'anthropic'|'openai'
     *  - key:         ?string       API key for the preferred provider (legacy api_key fallback included)
     *  - model:       string        model id for the preferred provider (never null)
     *
     * Pass $activeOnly=true to require Integration.is_active=1 (e.g. the
     * public chatbot path). The admin assistant path treats an inactive
     * integration as "configured but disabled" and may show a different
     * error, so it leaves the flag off.
     */
    public static function resolve(Tenant $tenant, bool $activeOnly = false): array
    {
        $integration = $tenant->getIntegration('ai_provider', $activeOnly);
        $cfg         = $integration?->config ?? [];

        $preferred = $cfg['preferred'] ?? ($integration?->provider ?? 'anthropic');

        $key = $preferred === 'openai'
            ? ($cfg['openai_key']    ?? $integration?->api_key ?? null)
            : ($cfg['anthropic_key'] ?? $integration?->api_key ?? null);

        $model = $preferred === 'openai'
            ? ($cfg['openai_model']    ?? $cfg['model'] ?? self::DEFAULT_OPENAI_MODEL)
            : ($cfg['anthropic_model'] ?? $cfg['model'] ?? self::DEFAULT_ANTHROPIC_MODEL);

        return compact('integration', 'preferred', 'key', 'model');
    }
}
