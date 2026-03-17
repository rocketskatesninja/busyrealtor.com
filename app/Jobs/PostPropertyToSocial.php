<?php

namespace App\Jobs;

use App\Models\Integration;
use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class PostPropertyToSocial implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly Property $property,
        public readonly string $event // 'new_listing' | 'sold'
    ) {}

    public function handle(): void
    {
        $property = $this->property->loadMissing('images', 'tenant');
        $tenantId = $property->tenant_id;

        // Facebook
        try {
            $fb = Integration::where('tenant_id', $tenantId)
                ->where('integration_type', 'facebook')
                ->where('is_active', true)
                ->first();
            if ($fb && $this->shouldPost($fb, $this->event)) {
                $this->postToFacebook($fb, $property);
            }
        } catch (\Throwable $e) {
            Log::error("Social auto-post: Facebook failed for property #{$property->id}: " . $e->getMessage());
        }

        // Twitter / X
        try {
            $tw = Integration::where('tenant_id', $tenantId)
                ->where('integration_type', 'twitter')
                ->where('is_active', true)
                ->first();
            if ($tw && $this->shouldPost($tw, $this->event)) {
                $this->postToTwitter($tw, $property);
            }
        } catch (\Throwable $e) {
            Log::error("Social auto-post: Twitter failed for property #{$property->id}: " . $e->getMessage());
        }
    }

    private function shouldPost(Integration $integration, string $event): bool
    {
        $config = $integration->config ?? [];
        if ($event === 'new_listing') return !empty($config['post_on_new_listing']);
        if ($event === 'sold')        return !empty($config['post_on_sold']);
        return false;
    }

    private function buildText(Property $property): string
    {
        $url = route('tenant.property', [
            'account' => $property->tenant->slug,
            'id'      => $property->id,
        ]);

        if ($this->event === 'sold') {
            return sprintf(
                "SOLD! %s — %sbd/%sba · $%s\n%s\n#realestate #sold #justclosed",
                $property->title,
                $property->bedrooms ?? '?',
                $property->bathrooms ?? '?',
                number_format($property->price),
                $url
            );
        }

        return sprintf(
            "New Listing: %s — %sbd/%sba · %s sqft · $%s\n%s\n#realestate #forsale",
            $property->title,
            $property->bedrooms ?? '?',
            $property->bathrooms ?? '?',
            $property->square_feet ? number_format($property->square_feet) : '?',
            number_format($property->price),
            $url
        );
    }

    private function getImageUrl(Property $property): ?string
    {
        $img = $property->images->firstWhere('is_primary', true) ?? $property->images->first();
        if (!$img) return null;
        return Storage::disk('public')->url($img->image_url);
    }

    private function postToFacebook(Integration $fb, Property $property): void
    {
        $token  = $fb->decryptKey();
        $config = $fb->config ?? [];
        $pageId = $config['page_id'] ?? null;

        if (!$token || !$pageId) {
            Log::warning("Social auto-post: Facebook missing token or page_id for tenant #{$property->tenant_id}");
            return;
        }

        $text     = $this->buildText($property);
        $imageUrl = $this->getImageUrl($property);

        if ($imageUrl) {
            $response = Http::post("https://graph.facebook.com/v19.0/{$pageId}/photos", [
                'access_token' => $token,
                'message'      => $text,
                'url'          => $imageUrl,
            ]);
        } else {
            $response = Http::post("https://graph.facebook.com/v19.0/{$pageId}/feed", [
                'access_token' => $token,
                'message'      => $text,
            ]);
        }

        if (!$response->successful()) {
            Log::error("Social auto-post: Facebook API error for property #{$property->id}: " . $response->body());
        }
    }

    private function postToTwitter(Integration $tw, Property $property): void
    {
        $apiKey            = $tw->decryptKey();
        $config            = $tw->config ?? [];
        $apiSecret         = $config['api_secret'] ?? null;
        $accessToken       = $config['access_token'] ?? null;
        $accessTokenSecret = $config['access_token_secret'] ?? null;

        if (!$apiKey || !$apiSecret || !$accessToken || !$accessTokenSecret) {
            Log::warning("Social auto-post: Twitter missing credentials for tenant #{$property->tenant_id}");
            return;
        }

        $connection = new \Abraham\TwitterOAuth\TwitterOAuth(
            $apiKey, $apiSecret, $accessToken, $accessTokenSecret
        );

        $text   = mb_substr($this->buildText($property), 0, 280);
        $params = ['text' => $text];

        $imageUrl = $this->getImageUrl($property);
        if ($imageUrl) {
            // Media upload still uses v1.1 — library expects a file path, not raw bytes
            $tmpFile = tempnam(sys_get_temp_dir(), 'tw_');
            $imageContents = @file_get_contents($imageUrl);
            if ($imageContents !== false && file_put_contents($tmpFile, $imageContents)) {
                $media = $connection->upload('media/upload', ['media' => $tmpFile]);
                @unlink($tmpFile);
                if (isset($media->media_id_string)) {
                    $params['media'] = ['media_ids' => [$media->media_id_string]];
                }
            } else {
                @unlink($tmpFile);
            }
        }

        $connection->setApiVersion('2');
        $result = $connection->post('tweets', $params, ['jsonPayload' => true]);

        if ($connection->getLastHttpCode() >= 400) {
            Log::error("Social auto-post: Twitter API error for property #{$property->id}: " . json_encode($result));
        }
    }
}
