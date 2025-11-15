<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;

class SitrepSynthesizer
{
    private LingoSdkService $lingo;

    public function __construct(LingoSdkService $lingo)
    {
        $this->lingo = $lingo;
    }

    /**
     * Synthesize a canonical English SITREP from a cluster of reports
     */
    public function synthesize(array $cluster): array
    {
        $reports = $cluster['reports'];
        $incidentId = $this->generateIncidentId($cluster);
        
        // Synthesize core information
        $title = $this->synthesizeTitle($reports);
        $location = $this->synthesizeLocation($reports);
        $timeWindow = $this->synthesizeTimeWindow($reports);
        $summary = $this->synthesizeSummary($reports);
        $details = $this->synthesizeDetails($reports);
        $casualties = $this->synthesizeCasualties($reports);
        $sources = $this->synthesizeSources($reports);
        $recommendedAction = $this->determineRecommendedAction($cluster);
        $audit = $this->generateAudit($reports);

        // Generate localized versions
        $localizedSummary = $this->lingo->compile($summary['en']);
        $localizedDetails = $this->generateLocalizedDetails($details['bullets_en']);

        return [
            'incident_id' => $incidentId,
            'canonical_title' => $title,
            'location' => $location,
            'time_window' => $timeWindow,
            'status' => $cluster['verification_status'],
            'sources' => $sources,
            'summary' => array_merge($summary, $localizedSummary),
            'details' => [
                'bullets_en' => $details['bullets_en'],
                'bullets_hi' => $localizedDetails['hi'] ?? [],
                'bullets_bn' => $localizedDetails['bn'] ?? [],
            ],
            'casualty_estimate' => $casualties,
            'recommended_action' => $recommendedAction,
            'audit' => $audit,
        ];
    }

    private function generateIncidentId(array $cluster): string
    {
        $timestamp = Carbon::now()->format('Ymd');
        $location = $this->getMainLocation($cluster['reports']);
        $locationSlug = Str::slug($location, '_');
        $eventType = $this->getMainEventType($cluster['reports']);
        
        return sprintf('%s_%s_%s_%s', 
            $timestamp, 
            $locationSlug, 
            $eventType, 
            substr(md5(json_encode($cluster)), 0, 6)
        );
    }

    private function synthesizeTitle(array $reports): string
    {
        $eventType = $this->getMainEventType($reports);
        $location = $this->getMainLocation($reports);
        $timestamp = $this->getMainTimestamp($reports);
        
        $timeStr = Carbon::parse($timestamp)->format('H:i');
        
        return sprintf('%s reported in %s at %s', 
            ucfirst($eventType), 
            $location, 
            $timeStr
        );
    }

    private function synthesizeLocation(array $reports): array
    {
        // First, try to use geocoded locations from user input
        $geotagged = array_filter($reports, fn($r) => 
            isset($r['geotag']) && 
            $r['geotag']['confidence'] > 0.1 && 
            $r['geotag']['lat'] !== null && 
            $r['geotag']['lng'] !== null
        );
        
        if (empty($geotagged)) {
            return [
                'name' => 'Unknown location',
                'lat' => null,
                'lng' => null,
                'confidence' => 0.0,
            ];
        }

        // Use the geotag with highest confidence
        usort($geotagged, fn($a, $b) => $b['geotag']['confidence'] <=> $a['geotag']['confidence']);
        $bestGeotag = $geotagged[0]['geotag'];
        
        // Get location name from user input or geotag display_name
        $locationName = $this->getMainLocation($reports);
        if ($locationName === 'Unknown location' && isset($bestGeotag['display_name'])) {
            $locationName = $bestGeotag['display_name'];
        }
        
        return [
            'name' => $locationName,
            'lat' => $bestGeotag['lat'],
            'lng' => $bestGeotag['lng'],
            'confidence' => $bestGeotag['confidence'],
        ];
    }

    private function synthesizeTimeWindow(array $reports): array
    {
        $timestamps = array_map(fn($r) => Carbon::parse($r['timestamp']), $reports);
        
        $firstReport = min($timestamps);
        $lastReport = max($timestamps);
        
        // Estimate event time based on reports
        $avgTime = $firstReport->copy()->addSeconds(
            ($lastReport->timestamp - $firstReport->timestamp) / 2
        );
        
        return [
            'first_report' => $firstReport->toISOString(),
            'last_report' => $lastReport->toISOString(),
            'approx_event_time' => $avgTime->toISOString(),
            'time_confidence' => count($reports) > 1 ? 0.7 : 0.5,
        ];
    }

    private function synthesizeSummary(array $reports): array
    {
        $eventType = $this->getMainEventType($reports);
        $location = $this->getMainLocation($reports);
        $casualties = $this->synthesizeCasualties($reports);
        
        $summary = sprintf(
            'A %s incident was reported in %s. %s',
            $eventType,
            $location,
            $this->formatCasualtySummary($casualties)
        );
        
        return ['en' => $summary];
    }

    private function synthesizeDetails(array $reports): array
    {
        $bullets = [];
        
        // Witness statements
        $witnessStatements = array_filter($reports, fn($r) => $r['witness_count'] > 0);
        if (!empty($witnessStatements)) {
            $totalWitnesses = array_sum(array_column($witnessStatements, 'witness_count'));
            $bullets[] = sprintf('Witnessed by approximately %d people', $totalWitnesses);
        }
        
        // Casualty information
        $casualtyReports = array_filter($reports, 
            fn($r) => isset($r['casualty_mentions']) && $r['casualty_confidence'] > 0.5
        );
        
        if (!empty($casualtyReports)) {
            $casualtyDetails = array_unique(array_map(
                fn($r) => $this->formatCasualtyMention($r['casualty_mentions']),
                $casualtyReports
            ));
            $bullets = array_merge($bullets, $casualtyDetails);
        } else {
            $bullets[] = 'No casualty information confirmed';
        }
        
        // Source information
        $sourceTypes = array_unique(array_column($reports, 'source_type'));
        $bullets[] = sprintf('Reported via: %s', implode(', ', $sourceTypes));
        
        // Certainty levels
        $certaintyLevels = array_unique(array_column($reports, 'certainty_level'));
        if (in_array('confirmed', $certaintyLevels)) {
            $bullets[] = 'Status: Confirmed reports present';
        } elseif (in_array('reported', $certaintyLevels)) {
            $bullets[] = 'Status: Unconfirmed reports';
        }
        
        return ['bullets_en' => $bullets];
    }

    private function synthesizeCasualties(array $reports): array
    {
        $casualtyReports = array_filter($reports, 
            fn($r) => isset($r['casualty_mentions']) && $r['casualty_confidence'] > 0.5
        );
        
        if (empty($casualtyReports)) {
            return [
                'mentioned_count' => null,
                'confidence' => 0.1,
            ];
        }
        
        // Check for explicit "no casualties"
        $noCasualtyReports = array_filter($casualtyReports, 
            fn($r) => $r['casualty_mentions'] === 0
        );
        
        if (!empty($noCasualtyReports)) {
            return [
                'mentioned_count' => 0,
                'confidence' => 0.8,
            ];
        }
        
        // Get numeric mentions
        $numericMentions = array_filter(
            array_map(fn($r) => $r['casualty_mentions'], $casualtyReports),
            'is_numeric'
        );
        
        if (!empty($numericMentions)) {
            $avgMentions = array_sum($numericMentions) / count($numericMentions);
            return [
                'mentioned_count' => (int)round($avgMentions),
                'confidence' => 0.7,
            ];
        }
        
        return [
            'mentioned_count' => null,
            'confidence' => 0.3,
        ];
    }

    private function synthesizeSources(array $reports): array
    {
        $reportIds = array_column($reports, 'id');
        $sources = array_unique(array_map(
            fn($r) => $r['reporter_meta']['source'] ?? 'unknown',
            $reports
        ));
        
        // Get top 3 most credible sources
        $sourceCredibility = [];
        foreach ($reports as $report) {
            $source = $report['reporter_meta']['source'] ?? 'unknown';
            $credibility = $report['reporter_meta']['credibility'] ?? 'unknown';
            
            if (!isset($sourceCredibility[$source])) {
                $sourceCredibility[$source] = ['count' => 0, 'credibility' => $credibility];
            }
            $sourceCredibility[$source]['count']++;
        }
        
        // Sort by count and credibility
        uasort($sourceCredibility, function($a, $b) {
            $credibilityOrder = ['high' => 3, 'medium' => 2, 'low' => 1, 'unknown' => 0];
            $aScore = $a['count'] + ($credibilityOrder[$a['credibility']] ?? 0);
            $bScore = $b['count'] + ($credibilityOrder[$b['credibility']] ?? 0);
            return $bScore <=> $aScore;
        });
        
        $top3Sources = array_slice(array_keys($sourceCredibility), 0, 3);
        
        return [
            'report_count' => count($reports),
            'report_ids' => $reportIds,
            'top_3_sources_summary' => $top3Sources,
        ];
    }

    private function determineRecommendedAction(array $cluster): string
    {
        $status = $cluster['verification_status'];
        $verificationScore = $cluster['verification_score'];
        $reportCount = count($cluster['reports']);
        
        if ($status === 'verified' || $verificationScore >= 0.8) {
            return 'publish';
        }
        
        if ($status === 'probable' || ($verificationScore >= 0.5 && $reportCount >= 2)) {
            return 'alert_authorities';
        }
        
        if ($reportCount >= 3) {
            return 'request_verification';
        }
        
        return 'monitor';
    }

    private function generateAudit(array $reports): array
    {
        $translations = [];
        $geocodeAttempts = [];
        
        foreach ($reports as $report) {
            // Translation audit
            $translations[] = [
                'report_id' => $report['id'],
                'en_text' => $report['english_text'],
                'hi_text' => $this->lingo->translate($report['english_text'], 'en', 'hi'),
                'bn_text' => $this->lingo->translate($report['english_text'], 'en', 'bn'),
            ];
            
            // Geocoding audit
            if (!empty($report['location_names'])) {
                foreach ($report['location_names'] as $location) {
                    $geocodeAttempts[] = [
                        'report_id' => $report['id'],
                        'query' => $location,
                        'result' => $report['geotag'],
                    ];
                }
            }
        }
        
        return [
            'translations' => $translations,
            'geocode_attempts' => $geocodeAttempts,
            'created_at' => Carbon::now()->toISOString(),
        ];
    }

    private function generateLocalizedDetails(array $englishBullets): array
    {
        $localized = ['hi' => [], 'bn' => []];
        
        foreach ($englishBullets as $bullet) {
            $translated = $this->lingo->compile($bullet, ['hi', 'bn']);
            $localized['hi'][] = $translated['hi'] ?? $bullet;
            $localized['bn'][] = $translated['bn'] ?? $bullet;
        }
        
        return $localized;
    }

    // Helper methods

    private function getMainEventType(array $reports): string
    {
        $eventTypes = array_column($reports, 'event_type');
        $eventCounts = array_count_values($eventTypes);
        
        unset($eventCounts['unknown']);
        arsort($eventCounts);
        
        return array_key_first($eventCounts) ?: 'incident';
    }

    private function getMainLocation(array $reports): string
    {
        // First, try to use the user-provided location field
        $userLocations = [];
        foreach ($reports as $report) {
            if (isset($report['location']) && !empty($report['location'])) {
                $userLocations[] = $report['location'];
            }
        }
        
        if (!empty($userLocations)) {
            $locationCounts = array_count_values($userLocations);
            arsort($locationCounts);
            return array_key_first($locationCounts);
        }
        
        // Fallback: use extracted location names from text
        $locations = [];
        foreach ($reports as $report) {
            $locations = array_merge($locations, $report['location_names'] ?? []);
        }
        
        if (empty($locations)) {
            return 'Unknown location';
        }
        
        $locationCounts = array_count_values($locations);
        arsort($locationCounts);
        
        return array_key_first($locationCounts);
    }

    private function getMainTimestamp(array $reports): string
    {
        $timestamps = array_column($reports, 'timestamp');
        sort($timestamps);
        
        return $timestamps[0]; // Return earliest timestamp
    }

    private function formatCasualtySummary(array $casualties): string
    {
        if ($casualties['mentioned_count'] === 0) {
            return 'No casualties reported.';
        }
        
        if ($casualties['mentioned_count'] > 0) {
            return sprintf('%d casualties mentioned.', $casualties['mentioned_count']);
        }
        
        return 'Casualty information unclear.';
    }

    private function formatCasualtyMention(mixed $mention): string
    {
        if ($mention === 0) {
            return 'No injuries reported';
        }
        
        if (is_numeric($mention)) {
            return sprintf('%d casualties mentioned', $mention);
        }
        
        return 'Casualties reported (number unclear)';
    }
}