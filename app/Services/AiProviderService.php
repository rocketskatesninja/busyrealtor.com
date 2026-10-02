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
    /**
     * Why the AI is or is not usable, for the admin screens to say out loud.
     *
     * resolve() reads only the preferred provider's key, which is the behaviour we want
     * -- a tenant who picks Anthropic should not have their visitors' conversations sent
     * to OpenAI because a key for it happens to be lying around. But the screens then
     * told them "add an AI provider" while the panel above said "Saved: OpenAI key",
     * which is how a working key and a dead chatbot sat on the same page unexplained.
     *
     * Returns:
     *  - ok:        bool    whether the preferred provider can actually be called
     *  - reason:    ?string null when ok; otherwise no_integration | inactive |
     *                       no_keys | no_key_for_preferred
     *  - preferred: string
     *  - keyed:     string[] providers that do have a key saved
     */
    public static function status(Tenant $tenant): array
    {
        $integration = $tenant->getIntegration('ai_provider');
        $cfg = $integration?->config ?? [];
        $preferred = $cfg['preferred'] ?? ($integration?->provider ?? 'anthropic');

        $keyed = [];
        foreach (['anthropic', 'openai'] as $provider) {
            if (filled($cfg["{$provider}_key"] ?? null)) {
                $keyed[] = $provider;
            }
        }
        // The legacy single-column key belongs to whichever provider is preferred.
        if (! $keyed && filled($integration?->api_key)) {
            $keyed[] = $preferred;
        }

        $reason = match (true) {
            ! $integration => 'no_integration',
            ! $integration->is_active => 'inactive',
            ! $keyed => 'no_keys',
            ! in_array($preferred, $keyed, true) => 'no_key_for_preferred',
            default => null,
        };

        return [
            'ok' => $reason === null,
            'reason' => $reason,
            'preferred' => $preferred,
            'keyed' => $keyed,
        ];
    }

    /** Provider ids as a tenant admin would read them. */
    public static function label(string $provider): string
    {
        return $provider === 'openai' ? 'OpenAI' : 'Anthropic';
    }

    /**
     * A sentence an admin can act on, or null when the AI is fine. Deliberately names
     * both ways out of a preference/key mismatch rather than choosing one for them.
     */
    public static function problem(Tenant $tenant): ?string
    {
        $status = self::status($tenant);
        $preferred = self::label($status['preferred']);

        return match ($status['reason']) {
            null => null,
            'no_integration', 'no_keys' => 'No AI provider key is saved yet.',
            'inactive' => 'The AI provider is switched off, so nothing can call it.',
            'no_key_for_preferred' => sprintf(
                'Your provider is set to %s, which has no key saved. Add a key for %s, or switch the provider to %s.',
                $preferred,
                $preferred,
                implode(' or ', array_map([self::class, 'label'], $status['keyed'])),
            ),
        };
    }

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
