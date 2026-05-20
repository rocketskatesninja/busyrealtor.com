<?php

namespace App\Services;

use App\Models\Property;
use App\Models\SystemSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GooglePlacesService
{
    private string $apiKey;

    public function __construct()
    {
        $sys = SystemSetting::current();
        $this->apiKey = $sys->google_places_key ?? $sys->google_maps_key ?? '';
    }

    /**
     * Fetch nearby schools, hospitals, and shopping for a property.
     * Returns structured array or null on failure.
     */
    public function fetchNearbyPlaces(Property $property): ?array
    {
        if (!$this->apiKey || !$property->latitude || !$property->longitude) {
            return null;
        }

        $lat = (float) $property->latitude;
        $lng = (float) $property->longitude;

        $categories = [
            'schools'   => ['school', 'primary_school', 'secondary_school'],
            'hospitals' => ['hospital'],
            'shopping'  => ['shopping_mall', 'supermarket', 'grocery_store'],
        ];

        $results = [];
        foreach ($categories as $key => $types) {
            $results[$key] = $this->searchCategory($lat, $lng, $types);
        }

        return $results;
    }

    /**
     * Search for nearby places of given types within 10 miles.
     */
    private function searchCategory(float $lat, float $lng, array $types, int $maxResults = 5): array
    {
        try {
            $response = Http::withHeaders([
                'X-Goog-Api-Key'   => $this->apiKey,
                'X-Goog-FieldMask' => 'places.displayName,places.formattedAddress,places.rating,places.location',
            ])->timeout(10)->post('https://places.googleapis.com/v1/places:searchNearby', [
                'includedTypes'       => $types,
                'maxResultCount'      => 10,
                'locationRestriction' => [
                    'circle' => [
                        'center' => ['latitude' => $lat, 'longitude' => $lng],
                        'radius' => 24140, // 15 miles in meters
                    ],
                ],
            ]);

            if (!$response->successful()) {
                Log::error('GooglePlacesService: API error', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                    'types'  => $types,
                ]);
                return [];
            }

            $places = $response->json('places') ?? [];

            // Map and calculate distance
            $mapped = array_map(function ($place) use ($lat, $lng) {
                $placeLat = $place['location']['latitude'] ?? null;
                $placeLng = $place['location']['longitude'] ?? null;
                $distance = ($placeLat && $placeLng)
                    ? self::haversineDistance($lat, $lng, $placeLat, $placeLng)
                    : null;

                return [
                    'name'           => $place['displayName']['text'] ?? 'Unknown',
                    'address'        => $place['formattedAddress'] ?? '',
                    'rating'         => $place['rating'] ?? null,
                    'distance_miles' => $distance ? round($distance, 1) : null,
                ];
            }, $places);

            // Sort by distance, take top N
            usort($mapped, fn($a, $b) => ($a['distance_miles'] ?? 999) <=> ($b['distance_miles'] ?? 999));

            return array_slice($mapped, 0, $maxResults);

        } catch (\Throwable $e) {
            Log::error('GooglePlacesService: exception', [
                'types' => $types,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Calculate distance between two points in miles using Haversine formula.
     */
    private static function haversineDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 3958.8; // miles

        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);

        $a = sin($dLat / 2) ** 2
           + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return $earthRadius * 2 * atan2(sqrt($a), sqrt(1 - $a));
    }
}
