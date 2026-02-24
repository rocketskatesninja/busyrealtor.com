<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\Request;

class GenerateDescriptionController extends Controller
{
    public function generate($account, Request $request)
    {
        $tenant  = app('tenant');
        if (!$tenant->isPro()) {
            return response()->json(['error' => 'AI description generation is a Pro plan feature. Please upgrade.'], 403);
        }
        $aiInteg = Integration::where('tenant_id', $tenant->id)->where('integration_type', 'ai_provider')->first();

        if (!$aiInteg || !$aiInteg->api_key) {
            return response()->json(['error' => 'AI not configured.'], 400);
        }

        $prompt = "Write a professional real estate listing description for this property:\n" .
            "Title: {$request->title}\nType: {$request->property_type}\nPrice: \${$request->price}\n" .
            "Beds: {$request->bedrooms}, Baths: {$request->bathrooms}, Sqft: {$request->sqft}\n" .
            "Address: {$request->address}, {$request->city}, {$request->state}\n" .
            "Features: {$request->amenities}\n\nWrite 2-3 compelling paragraphs. Be specific and enthusiastic.";

        try {
            $config = $aiInteg->config ?? [];
            if ($aiInteg->provider === 'openai') {
                $client = \OpenAI::client($aiInteg->api_key);
                $resp   = $client->chat()->create(['model' => $config['model'] ?? 'gpt-4o-mini', 'messages' => [['role' => 'user', 'content' => $prompt]], 'max_tokens' => 600]);
                $text   = $resp->choices[0]->message->content;
            } else {
                $client = \Anthropic::client($aiInteg->api_key);
                $resp   = $client->messages()->create(['model' => $config['model'] ?? 'claude-haiku-4-5-20251001', 'max_tokens' => 600, 'messages' => [['role' => 'user', 'content' => $prompt]]]);
                $text   = $resp->content[0]->text;
            }
            return response()->json(['description' => $text]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
