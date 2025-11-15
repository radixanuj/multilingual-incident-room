<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class GeocodingService
{
    private Client $httpClient;
    private array $locationDatabase;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 120, // 2 minutes timeout
            'connect_timeout' => 30, // 30 seconds connection timeout
        ]);
        
        // No static location database - use real geocoding API for everything
        $this->locationDatabase = [];
    }

        /**
     * Resolve location string to lat/lng coordinates using external geocoding API
     * @param string $locationString The location to resolve
     * @return array Result with lat, lng, confidence, and source
     */
    public function resolve(string $locationString): array
    {
        $normalizedLocation = $this->normalizeLocationString($locationString);

        // ALWAYS use external geocoding service (real API call only)
        try {
            $externalResult = $this->geocodeWithNominatim($normalizedLocation);
            if ($externalResult['confidence'] >= 0.4) {
                Log::info("Successfully geocoded via API", [
                    'location' => $locationString,
                    'result' => $externalResult
                ]);
                return $externalResult;
            }
        } catch (\Exception $e) {
            Log::warning('External geocoding failed: ' . $e->getMessage());
        }

        // Final fallback - return with very low confidence
        Log::warning("Geocoding completely failed for: " . $locationString);
        return [
            'lat' => null,
            'lng' => null,
            'confidence' => 0.0,
            'source' => 'geocoding_failed',
            'query' => $locationString,
            'display_name' => $locationString,
        ];
    }

    /**
     * Normalize location string for consistent matching
     */
    private function normalizeLocationString(string $location): string
    {
        $normalized = strtolower(trim($location));
        
        // Remove common prefixes and suffixes
        $normalized = preg_replace('/^(near|in|at|around)\s+/', '', $normalized);
        $normalized = preg_replace('/\s+(area|region|district|zone)$/', '', $normalized);
        
        // Normalize common variations
        $variations = [
            'new delhi' => 'delhi',
            'bengaluru' => 'bangalore',
            'kolkatta' => 'kolkata',
            'bombay' => 'mumbai',
            'madras' => 'chennai',
        ];
        
        return $variations[$normalized] ?? $normalized;
    }

    /**
     * Use Nominatim OpenStreetMap API for geocoding (worldwide search)
     */
    private function geocodeWithNominatim(string $location): array
    {
        $url = 'https://nominatim.openstreetmap.org/search';
        $params = [
            'q' => $location, // Search worldwide, no country restriction
            'format' => 'json',
            'limit' => 1,
            'addressdetails' => 1, // Get detailed address info
        ];

        $response = $this->httpClient->get($url, [
            'query' => $params,
            'headers' => [
                'User-Agent' => 'IncidentRoom/1.0 (emergency-response)',
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        if (empty($data)) {
            Log::warning("No geocoding results found for: " . $location);
            return ['confidence' => 0.0];
        }

        $result = $data[0];
        $confidence = $this->calculateNominatimConfidence($result, $location);

        Log::info("Geocoding API success", [
            'query' => $location,
            'lat' => $result['lat'],
            'lng' => $result['lon'],
            'confidence' => $confidence,
            'display_name' => $result['display_name']
        ]);

        return [
            'lat' => (float) $result['lat'],
            'lng' => (float) $result['lon'],
            'confidence' => $confidence,
            'source' => 'nominatim_osm',
            'query' => $location,
            'display_name' => $result['display_name'] ?? null,
        ];
    }

    /**
     * Calculate confidence score for Nominatim results
     */
    private function calculateNominatimConfidence(array $result, string $query): float
    {
        $importance = (float) ($result['importance'] ?? 0.5);
        $baseConfidence = min($importance * 2, 0.9); // Scale importance to 0-0.9

        // Bonus for exact name match
        $displayName = strtolower($result['display_name'] ?? '');
        if (str_contains($displayName, strtolower($query))) {
            $baseConfidence += 0.1;
        }

        return min($baseConfidence, 1.0);
    }

    /**
     * Get area boundaries for broad location queries
     * Note: This could be enhanced with dynamic API calls to get real bounds
     */
    public function getAreaBounds(string $location): array
    {
        // Return empty array - no static bounds defined
        // This method exists for interface compatibility but should use dynamic data
        Log::info("Area bounds requested for: " . $location);
        return [];
    }
}
