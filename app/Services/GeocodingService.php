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
            'timeout' => 10,
        ]);

        // Initialize local database of known locations in India for fallback
        $this->locationDatabase = [
            'karol bagh' => ['lat' => 28.6531, 'lng' => 77.1900, 'confidence' => 0.95],
            'delhi' => ['lat' => 28.6139, 'lng' => 77.2090, 'confidence' => 0.90],
            'new delhi' => ['lat' => 28.6139, 'lng' => 77.2090, 'confidence' => 0.90],
            'connaught place' => ['lat' => 28.6315, 'lng' => 77.2167, 'confidence' => 0.85],
            'india gate' => ['lat' => 28.6129, 'lng' => 77.2295, 'confidence' => 0.85],
            'mumbai' => ['lat' => 19.0760, 'lng' => 72.8777, 'confidence' => 0.90],
            'kolkata' => ['lat' => 22.5726, 'lng' => 88.3639, 'confidence' => 0.90],
            'bengaluru' => ['lat' => 12.9716, 'lng' => 77.5946, 'confidence' => 0.90],
            'bangalore' => ['lat' => 12.9716, 'lng' => 77.5946, 'confidence' => 0.90],
            'chennai' => ['lat' => 13.0827, 'lng' => 80.2707, 'confidence' => 0.90],
        ];
    }

    /**
     * Resolve location string to latitude/longitude coordinates
     *
     * @param string $locationString Location name to geocode
     * @return array Result with lat, lng, confidence, and source
     */
    public function resolve(string $locationString): array
    {
        $normalizedLocation = $this->normalizeLocationString($locationString);

        // Try local database first for known Indian locations
        $localResult = $this->searchLocalDatabase($normalizedLocation);
        if ($localResult['confidence'] >= 0.7) {
            return $localResult;
        }

        // Try external geocoding service (Nominatim OpenStreetMap)
        try {
            $externalResult = $this->geocodeWithNominatim($normalizedLocation);
            if ($externalResult['confidence'] >= 0.5) {
                return $externalResult;
            }
        } catch (\Exception $e) {
            Log::warning('External geocoding failed: ' . $e->getMessage());
        }

        // Fallback to fuzzy matching in local database
        $fuzzyResult = $this->fuzzySearchLocal($normalizedLocation);
        if ($fuzzyResult['confidence'] >= 0.3) {
            return $fuzzyResult;
        }

        // Final fallback - return Delhi center with low confidence
        return [
            'lat' => 28.6139,
            'lng' => 77.2090,
            'confidence' => 0.1,
            'source' => 'fallback_delhi_center',
            'query' => $locationString,
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
     * Search in local database for exact or partial matches
     */
    private function searchLocalDatabase(string $location): array
    {
        // Exact match
        if (isset($this->locationDatabase[$location])) {
            $result = $this->locationDatabase[$location];
            return array_merge($result, [
                'source' => 'local_database_exact',
                'query' => $location,
            ]);
        }

        // Partial match
        foreach ($this->locationDatabase as $dbLocation => $coords) {
            if (str_contains($dbLocation, $location) || str_contains($location, $dbLocation)) {
                $confidence = $coords['confidence'] * 0.8; // Reduce confidence for partial match
                return [
                    'lat' => $coords['lat'],
                    'lng' => $coords['lng'],
                    'confidence' => $confidence,
                    'source' => 'local_database_partial',
                    'query' => $location,
                ];
            }
        }

        return ['confidence' => 0.0];
    }

    /**
     * Use Nominatim OpenStreetMap API for geocoding
     */
    private function geocodeWithNominatim(string $location): array
    {
        $url = 'https://nominatim.openstreetmap.org/search';
        $params = [
            'q' => $location . ', India',
            'format' => 'json',
            'limit' => 1,
            'countrycodes' => 'in',
        ];

        $response = $this->httpClient->get($url, [
            'query' => $params,
            'headers' => [
                'User-Agent' => 'IncidentRoom/1.0 (emergency-response)',
            ],
        ]);

        $data = json_decode($response->getBody()->getContents(), true);

        if (empty($data)) {
            return ['confidence' => 0.0];
        }

        $result = $data[0];
        $confidence = $this->calculateNominatimConfidence($result, $location);

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
     * Fuzzy search in local database using Levenshtein distance
     */
    private function fuzzySearchLocal(string $location): array
    {
        $bestMatch = null;
        $lowestDistance = PHP_INT_MAX;

        foreach ($this->locationDatabase as $dbLocation => $coords) {
            $distance = levenshtein($location, $dbLocation);
            if ($distance < $lowestDistance && $distance <= 3) {
                $lowestDistance = $distance;
                $bestMatch = $coords;
                $bestMatch['matched_location'] = $dbLocation;
            }
        }

        if ($bestMatch) {
            // Calculate confidence based on string similarity
            $maxLen = max(strlen($location), strlen($bestMatch['matched_location']));
            $similarity = 1 - ($lowestDistance / $maxLen);
            $confidence = $similarity * $bestMatch['confidence'] * 0.6; // Reduce for fuzzy match

            return [
                'lat' => $bestMatch['lat'],
                'lng' => $bestMatch['lng'],
                'confidence' => $confidence,
                'source' => 'local_database_fuzzy',
                'query' => $location,
                'matched_location' => $bestMatch['matched_location'],
            ];
        }

        return ['confidence' => 0.0];
    }

    /**
     * Get area boundaries for broad location queries
     */
    public function getAreaBounds(string $location): array
    {
        $normalized = $this->normalizeLocationString($location);
        
        // Define bounds for major areas
        $areaBounds = [
            'delhi' => [
                'north' => 28.88,
                'south' => 28.40,
                'east' => 77.35,
                'west' => 76.84,
            ],
            'mumbai' => [
                'north' => 19.30,
                'south' => 18.89,
                'east' => 72.98,
                'west' => 72.77,
            ],
        ];

        return $areaBounds[$normalized] ?? [];
    }
}