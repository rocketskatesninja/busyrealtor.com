<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Integration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GenerateDescriptionController extends Controller
{
    public function generate($account, Request $request)
    {
        $tenant = app('tenant');
        if (!$tenant->isPro()) {
            return response()->json(['error' => 'AI description generation is a Pro plan feature. Please upgrade.'], 403);
        }

        $aiInteg = Integration::where('tenant_id', $tenant->id)
            ->where('integration_type', 'ai_provider')
            ->first();

        $config    = $aiInteg?->config ?? [];
        $preferred = $config['preferred'] ?? 'anthropic';
        $key       = $preferred === 'openai'
            ? ($config['openai_key'] ?? null)
            : ($config['anthropic_key'] ?? null);
        $model     = $preferred === 'openai'
            ? ($config['openai_model'] ?? 'gpt-4o-mini')
            : ($config['anthropic_model'] ?? 'claude-haiku-4-5-20251001');

        if (!$key) {
            return response()->json(['error' => 'AI not configured. Please add API keys in Settings → Chatbot & AI.'], 400);
        }

        $prompt = "Write a professional real estate listing description for this property:\n" .
            "Title: {$request->title}\nType: {$request->property_type}\nPrice: \${$request->price}\n" .
            "Beds: {$request->bedrooms}, Baths: {$request->bathrooms}, Sqft: {$request->sqft}\n" .
            "Address: {$request->address}, {$request->city}, {$request->state}\n" .
            "Features: {$request->amenities}\n\nWrite 2-3 compelling paragraphs. Be specific and enthusiastic.";

        try {
            if ($preferred === 'openai') {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . $key,
                ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
                    'model'      => $model,
                    'max_tokens' => 600,
                    'messages'   => [['role' => 'user', 'content' => $prompt]],
                ]);

                if ($response->failed()) {
                    return response()->json(['error' => 'AI service error: ' . $response->status()], 500);
                }

                $text = $response->json('choices.0.message.content') ?? '';
            } else {
                $response = Http::withHeaders([
                    'x-api-key'         => $key,
                    'anthropic-version' => '2023-06-01',
                ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
                    'model'      => $model,
                    'max_tokens' => 600,
                    'messages'   => [['role' => 'user', 'content' => $prompt]],
                ]);

                if ($response->failed()) {
                    return response()->json(['error' => 'AI service error: ' . $response->status()], 500);
                }

                $text = $response->json('content.0.text') ?? '';
            }

            return response()->json(['description' => $text]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
