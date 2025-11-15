<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReportProcessingPipeline
{
    private LingoSdkService $lingo;
    private GeocodingService $geocoding;
    
    // Configuration from the specification
    private const DEDUPE_SIMILARITY_THRESHOLD = 0.82;
    private const CLUSTER_DISTANCE_METERS = 2000;
    private const MIN_REPORTS_FOR_INCIDENT = 1;

    public function __construct(LingoSdkService $lingo, GeocodingService $geocoding)
    {
        $this->lingo = $lingo;
        $this->geocoding = $geocoding;
    }

    /**
     * Process a batch of reports through the complete pipeline
     */
    public function processReports(array $reports): array
    {
        // Step 1: Ingest and validate
        $validReports = $this->ingestReports($reports);
        
        // Step 2: Normalize text
        $normalizedReports = $this->normalizeReports($validReports);
        
        // Step 3: Translate to English
        $translatedReports = $this->translateReports($normalizedReports);
        
        // Step 4: Extract structured fields
        $extractedReports = $this->extractStructuredFields($translatedReports);
        
        // Step 5: Geotag locations
        $geotaggedReports = $this->geotagReports($extractedReports);
        
        // Step 6: Cluster and deduplicate
        $clusters = $this->clusterAndDedupe($geotaggedReports);
        
        // Step 7: Verify minimal facts
        $verifiedClusters = $this->verifyMinimalFacts($clusters);
        
        return $verifiedClusters;
    }

    /**
     * Step 1: Ingest and validate reports
     */
    private function ingestReports(array $reports): array
    {
        $validReports = [];
        
        foreach ($reports as $report) {
            // Validate required fields
            if (!isset($report['id']) || !isset($report['raw_text'])) {
                Log::warning('Report missing required fields', ['report' => $report]);
                continue;
            }
            
            // Add default values
            $report['original_language'] = $report['original_language'] ?? 'auto';
            $report['source_type'] = $report['source_type'] ?? 'text';
            $report['timestamp'] = $report['timestamp'] ?? now()->toISOString();
            $report['reporter_meta'] = $report['reporter_meta'] ?? ['source' => 'unknown', 'credibility' => 'unknown'];
            
            $validReports[] = $report;
        }
        
        return $validReports;
    }

    /**
     * Step 2: Normalize raw text
     */
    private function normalizeReports(array $reports): array
    {
        foreach ($reports as &$report) {
            $text = $report['raw_text'];
            
            // Remove filler tokens common in voice transcripts
            $fillerWords = ['uh', 'um', 'basically', 'you know', 'like'];
            foreach ($fillerWords as $filler) {
                $text = preg_replace('/\b' . preg_quote($filler, '/') . '\b/i', '', $text);
            }
            
            // Fix encoding issues
            $text = mb_convert_encoding($text, 'UTF-8', 'auto');
            
            // Normalize whitespace
            $text = preg_replace('/\s+/', ' ', $text);
            
            // Remove excessive punctuation
            $text = preg_replace('/[।.]{3,}/', '।', $text);
            $text = preg_replace('/[!]{2,}/', '!', $text);
            
            // Extract inline timestamps and locations
            $extractedData = $this->extractInlineData($text);
            $report['extracted_timestamp'] = $extractedData['timestamp'];
            $report['extracted_locations'] = $extractedData['locations'];
            
            $report['normalized_text'] = trim($text);
        }
        
        return $reports;
    }

    /**
     * Step 3: Translate all reports to English
     */
    private function translateReports(array $reports): array
    {
        foreach ($reports as &$report) {
            $originalLang = $report['original_language'];
            
            if ($originalLang === 'auto') {
                $originalLang = $this->lingo->detectLanguage($report['normalized_text']);
                $report['detected_language'] = $originalLang;
            }
            
            // Translate to English
            $report['english_text'] = $this->lingo->translate(
                $report['normalized_text'],
                $originalLang,
                'en'
            );
        }
        
        return $reports;
    }

    /**
     * Step 4: Extract structured fields from English text
     */
    private function extractStructuredFields(array $reports): array
    {
        foreach ($reports as &$report) {
            $text = $report['english_text'];
            
            // Extract event type
            $eventType = $this->extractEventType($text);
            $report['event_type'] = $eventType['type'];
            $report['event_confidence'] = $eventType['confidence'];
            
            // Extract location names
            $locations = $this->extractLocationNames($text, $report['extracted_locations'] ?? []);
            $report['location_names'] = $locations;
            
            // Extract datetime
            $datetime = $this->extractDateTime($text, $report);
            $report['best_guess_datetime'] = $datetime['datetime'];
            $report['datetime_confidence'] = $datetime['confidence'];
            
            // Extract casualties
            $casualties = $this->extractCasualties($text);
            $report['casualty_mentions'] = $casualties['mentions'];
            $report['casualty_confidence'] = $casualties['confidence'];
            
            // Extract witness count
            $report['witness_count'] = $this->extractWitnessCount($text);
            
            // Extract modal indicators
            $report['certainty_level'] = $this->extractCertaintyLevel($text);
        }
        
        return $reports;
    }

    /**
     * Step 5: Geotag reports with coordinates
     */
    private function geotagReports(array $reports): array
    {
        foreach ($reports as &$report) {
            $bestGeotag = null;
            $highestConfidence = 0;
            
            // Try each extracted location
            foreach ($report['location_names'] as $location) {
                $geotag = $this->geocoding->resolve($location);
                
                if ($geotag['confidence'] > $highestConfidence) {
                    $highestConfidence = $geotag['confidence'];
                    $bestGeotag = $geotag;
                }
            }
            
            $report['geotag'] = $bestGeotag ?: [
                'lat' => null,
                'lng' => null,
                'confidence' => 0.0,
                'source' => 'no_location_found',
            ];
        }
        
        return $reports;
    }

    /**
     * Step 6: Cluster similar reports and deduplicate
     */
    private function clusterAndDedupe(array $reports): array
    {
        $clusters = [];
        
        foreach ($reports as $report) {
            $assignedToCluster = false;
            
            // Try to assign to existing cluster
            foreach ($clusters as &$cluster) {
                if ($this->shouldAssignToCluster($report, $cluster)) {
                    $cluster['reports'][] = $report;
                    $assignedToCluster = true;
                    break;
                }
            }
            
            // Create new cluster if not assigned
            if (!$assignedToCluster) {
                $clusters[] = [
                    'id' => 'cluster_' . count($clusters) + 1,
                    'reports' => [$report],
                ];
            }
        }
        
        // Filter clusters by minimum report count
        return array_filter($clusters, function($cluster) {
            return count($cluster['reports']) >= self::MIN_REPORTS_FOR_INCIDENT;
        });
    }

    /**
     * Step 7: Verify minimal facts for each cluster
     */
    private function verifyMinimalFacts(array $clusters): array
    {
        foreach ($clusters as &$cluster) {
            $reports = $cluster['reports'];
            $reportCount = count($reports);
            
            // Calculate verification metrics
            $avgGeoConfidence = $this->calculateAverageGeoConfidence($reports);
            $hasCredibleSources = $this->hasCredibleSources($reports);
            $consistentEventType = $this->hasConsistentEventType($reports);
            
            // Compute verification score
            $verificationScore = ($reportCount * 0.3) + 
                               ($avgGeoConfidence * 0.4) + 
                               ($hasCredibleSources ? 0.2 : 0) +
                               ($consistentEventType ? 0.1 : 0);
            
            $verificationScore = min($verificationScore, 1.0);
            
            // Assign verification status
            if ($verificationScore >= 0.75) {
                $cluster['verification_status'] = 'verified';
            } elseif ($verificationScore >= 0.5) {
                $cluster['verification_status'] = 'probable';
            } else {
                $cluster['verification_status'] = 'unverified';
            }
            
            $cluster['verification_score'] = $verificationScore;
        }
        
        return $clusters;
    }

    // Helper methods for field extraction

    private function extractEventType(string $text): array
    {
        $eventPatterns = [
            'explosion' => ['explosion', 'blast', 'bomb', 'exploded', 'blow up', 'धमाका', 'বিস্ফোরণ'],
            'fire' => ['fire', 'burning', 'flames', 'smoke', 'आग', 'অগ্নি'],
            'collapse' => ['collapse', 'building fell', 'structure down', 'गिरना', 'ধসে পড়া'],
            'protest' => ['protest', 'demonstration', 'rally', 'crowd', 'प्रदर्शन', 'বিক্ষোভ'],
            'shooting' => ['shooting', 'gunfire', 'shots', 'गोली', 'গুলি'],
            'accident' => ['accident', 'crash', 'collision', 'दुर्घटना', 'দুর্ঘটনা'],
        ];

        $highestScore = 0;
        $detectedType = 'unknown';

        foreach ($eventPatterns as $type => $patterns) {
            $score = 0;
            foreach ($patterns as $pattern) {
                if (stripos($text, $pattern) !== false) {
                    $score += 1;
                }
            }

            if ($score > $highestScore) {
                $highestScore = $score;
                $detectedType = $type;
            }
        }

        $confidence = min($highestScore * 0.3, 1.0);

        return [
            'type' => $detectedType,
            'confidence' => $confidence,
        ];
    }

    private function extractLocationNames(string $text, array $inlineLocations = []): array
    {
        $locations = array_merge($inlineLocations, []);

        // Common Indian location patterns
        $locationPatterns = [
            '/\b([A-Z][a-z]+\s+[Bb]agh)\b/',  // Karol Bagh, etc.
            '/\b([A-Z][a-z]+\s+[Nn]agar)\b/', // Areas with Nagar
            '/\b([A-Z][a-z]+\s+[Pp]lace)\b/', // Connaught Place, etc.
            '/\b(Delhi|Mumbai|Kolkata|Chennai|Bangalore|Hyderabad)\b/i',
        ];

        foreach ($locationPatterns as $pattern) {
            if (preg_match_all($pattern, $text, $matches)) {
                $locations = array_merge($locations, $matches[1]);
            }
        }

        return array_unique($locations);
    }

    private function extractDateTime(string $text, array $report): array
    {
        $baseTime = Carbon::parse($report['timestamp']);
        
        // Look for time indicators in text
        $timePatterns = [
            '/(\d{1,2}):(\d{2})\s*(AM|PM)/i',
            '/(\d{1,2})\s*बजे/',
            '/সকাল|দুপুর|বিকাল|রাত/',
        ];

        foreach ($timePatterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return [
                    'datetime' => $baseTime->toISOString(),
                    'confidence' => 0.7,
                ];
            }
        }

        return [
            'datetime' => $baseTime->toISOString(),
            'confidence' => 0.3,
        ];
    }

    private function extractCasualties(string $text): array
    {
        // Look for casualty mentions
        if (preg_match('/no\s+(casualt|injur|hurt)/i', $text) || 
            preg_match('/कोई\s+चोट\s+नहीं|আঘাত\s+নেই/', $text)) {
            return [
                'mentions' => 0,
                'confidence' => 0.8,
            ];
        }

        // Look for numbers with casualty words
        if (preg_match('/(\d+)\s+(injured|hurt|casualt|dead|killed)/i', $text, $matches)) {
            return [
                'mentions' => (int)$matches[1],
                'confidence' => 0.9,
            ];
        }

        // Look for vague mentions
        if (preg_match('/several|many|few|some.*(injured|hurt|casualt)/i', $text)) {
            return [
                'mentions' => null,
                'confidence' => 0.4,
            ];
        }

        return [
            'mentions' => null,
            'confidence' => 0.1,
        ];
    }

    private function extractWitnessCount(string $text): ?int
    {
        if (preg_match('/(\d+)\s+(people|witnesses|persons).*(saw|heard|reported)/i', $text, $matches)) {
            return (int)$matches[1];
        }

        if (preg_match('/(many|several)\s+(people|witnesses)/i', $text)) {
            return 5; // Estimate for "many"
        }

        return null;
    }

    private function extractCertaintyLevel(string $text): string
    {
        if (preg_match('/confirmed|verified|official/i', $text)) {
            return 'confirmed';
        }

        if (preg_match('/reported|alleged|suspected/i', $text)) {
            return 'reported';
        }

        return 'unconfirmed';
    }

    private function extractInlineData(string $text): array
    {
        return [
            'timestamp' => null,
            'locations' => [],
        ];
    }

    private function shouldAssignToCluster(array $report, array $cluster): bool
    {
        $clusterReport = $cluster['reports'][0];
        
        // Check spatial proximity
        if ($report['geotag']['lat'] && $clusterReport['geotag']['lat']) {
            $distance = $this->calculateDistance(
                $report['geotag']['lat'],
                $report['geotag']['lng'],
                $clusterReport['geotag']['lat'],
                $clusterReport['geotag']['lng']
            );
            
            if ($distance > self::CLUSTER_DISTANCE_METERS) {
                return false;
            }
        }
        
        // Check time proximity (same hour)
        $reportTime = Carbon::parse($report['timestamp']);
        $clusterTime = Carbon::parse($clusterReport['timestamp']);
        
        if (abs($reportTime->diffInHours($clusterTime)) > 1) {
            return false;
        }
        
        // Check semantic similarity
        $similarity = $this->calculateTextSimilarity(
            $report['english_text'],
            $clusterReport['english_text']
        );
        
        return $similarity >= self::DEDUPE_SIMILARITY_THRESHOLD;
    }

    private function calculateDistance(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earthRadius = 6371000; // meters
        
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        
        $a = sin($dLat/2) * sin($dLat/2) +
             cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
             sin($dLng/2) * sin($dLng/2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        
        return $earthRadius * $c;
    }

    private function calculateTextSimilarity(string $text1, string $text2): float
    {
        // Simple word-based similarity calculation
        $words1 = array_unique(str_word_count(strtolower($text1), 1));
        $words2 = array_unique(str_word_count(strtolower($text2), 1));
        
        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));
        
        return $union > 0 ? $intersection / $union : 0;
    }

    private function calculateAverageGeoConfidence(array $reports): float
    {
        $total = 0;
        foreach ($reports as $report) {
            $total += $report['geotag']['confidence'];
        }
        return count($reports) > 0 ? $total / count($reports) : 0;
    }

    private function hasCredibleSources(array $reports): bool
    {
        foreach ($reports as $report) {
            $credibility = $report['reporter_meta']['credibility'] ?? 'unknown';
            if (in_array($credibility, ['high', 'verified', 'official'])) {
                return true;
            }
        }
        return false;
    }

    private function hasConsistentEventType(array $reports): bool
    {
        $eventTypes = array_map(fn($r) => $r['event_type'] ?? 'unknown', $reports);
        $uniqueTypes = array_unique($eventTypes);
        return count($uniqueTypes) === 1 && $uniqueTypes[0] !== 'unknown';
    }
}