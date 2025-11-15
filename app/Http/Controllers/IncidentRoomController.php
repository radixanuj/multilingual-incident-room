<?php

namespace App\Http\Controllers;

use App\Services\LingoSdkService;
use App\Services\GeocodingService;
use App\Services\ReportProcessingPipeline;
use App\Services\SitrepSynthesizer;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IncidentRoomController extends Controller
{
    private const DEFAULT_TEST_LOCATION = 'Karol Bagh, Delhi';
    
    private ReportProcessingPipeline $pipeline;
    private SitrepSynthesizer $synthesizer;
    private GeocodingService $geocodingService;

    public function __construct(
        LingoSdkService $lingo,
        GeocodingService $geocoding
    ) {
        $this->pipeline = new ReportProcessingPipeline($lingo, $geocoding);
        $this->synthesizer = new SitrepSynthesizer($lingo);
        $this->geocodingService = $geocoding;
    }

    /**
     * Process incident reports and generate SITREP
     */
    public function processReports(Request $request): JsonResponse
    {
        // Increase PHP execution time limit for processing
        set_time_limit(300); // 5 minutes
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M'); // Increase memory limit
        ignore_user_abort(true); // Continue processing even if user disconnects
        
        try {
            // Validate input
            $validated = $request->validate([
                'reports' => 'required|array|min:1|max:10',
                'reports.*.raw_text' => 'required|string',
                'reports.*.location' => 'required|string',
                'reports.*.original_language' => 'nullable|string',
                'reports.*.source_type' => 'nullable|string',
                'reports.*.timestamp' => 'nullable|string',
                'reports.*.reporter_meta' => 'nullable|array',
            ]);

            $reports = $validated['reports'];

            // Auto-generate IDs and process locations
            foreach ($reports as $index => &$report) {
                // Generate unique ID for each report
                $report['id'] = 'r' . uniqid() . '_' . $index;
                
                // Geocode the location
                try {
                    $geocoded = $this->geocodingService->resolve($report['location']);
                    $report['geocoded_location'] = $geocoded;
                    Log::info("Geocoded location for report {$report['id']}", [
                        'location' => $report['location'],
                        'coordinates' => $geocoded,
                    ]);
                } catch (\Exception $e) {
                    Log::warning("Failed to geocode location for report {$report['id']}", [
                        'location' => $report['location'],
                        'error' => $e->getMessage(),
                    ]);
                    $report['geocoded_location'] = [
                        'lat' => null,
                        'lng' => null,
                        'confidence' => 0,
                        'display_name' => $report['location']
                    ];
                }
            }

            Log::info('Processing incident reports', [
                'report_count' => count($reports),
                'report_ids' => array_column($reports, 'id'),
            ]);

            // Run the complete pipeline
            $clusters = $this->pipeline->processReports($reports);

            if (empty($clusters)) {
                return response()->json([
                    'error' => 'No valid incidents could be formed from the provided reports',
                    'message' => 'Reports may be too dissimilar or lack sufficient confidence scores',
                ], 422);
            }

            // Process the highest-confidence cluster
            $primaryCluster = $this->selectPrimaryCluster($clusters);
            $sitrep = $this->synthesizer->synthesize($primaryCluster);

            // Apply quality checks
            $validatedSitrep = $this->applyQualityChecks($sitrep);

            // Save SITREP to file
            $this->saveSitrepToFile($validatedSitrep);

            Log::info('SITREP generated successfully', [
                'incident_id' => $validatedSitrep['incident_id'],
                'status' => $validatedSitrep['status'],
                'report_count' => $validatedSitrep['sources']['report_count'],
            ]);

            return response()->json($validatedSitrep, 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors(),
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error processing reports', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Internal processing error',
                'message' => 'Failed to process incident reports',
            ], 500);
        }
    }

    /**
     * Test endpoint with example data
     */
    public function testWithExampleData(): JsonResponse
    {
        // Increase PHP execution time limit for processing
        set_time_limit(300); // 5 minutes
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M'); // Increase memory limit
        ignore_user_abort(true); // Continue processing even if user disconnects
        
        $exampleReports = [
            [
                'raw_text' => 'दिल्ली के करोल बाग में एक जोरदार धमाका हुआ, बहुत तेज आवाज़, कई लोगों ने सुना, अभी तक चोट की खबर नहीं मिली',
                'location' => self::DEFAULT_TEST_LOCATION,
                'original_language' => 'hi',
                'source_type' => 'voice-transcript',
                'timestamp' => '2025-11-15T07:12:00+05:30',
                'reporter_meta' => ['source' => 'field_call', 'credibility' => 'unknown'],
            ],
            [
                'raw_text' => 'করোল বাগ এলাকায় বিস্ফোরণ শোনা গেছে, লোকেরা বাইরে হয়েছে, কেউ আহত হয়েছে জানি না',
                'location' => self::DEFAULT_TEST_LOCATION,
                'original_language' => 'bn',
                'source_type' => 'voice-transcript',
                'timestamp' => '2025-11-15T07:13:27+05:30',
                'reporter_meta' => ['source' => 'citizen_sms', 'credibility' => 'unknown'],
            ],
            [
                'raw_text' => 'Loud explosion reported near Karol Bagh, Delhi. Many people heard the blast. No confirmed casualties yet.',
                'location' => self::DEFAULT_TEST_LOCATION,
                'original_language' => 'en',
                'source_type' => 'text',
                'timestamp' => '2025-11-15T07:11:50+05:30',
                'reporter_meta' => ['source' => 'social_media_scrape', 'credibility' => 'low'],
            ],
        ];

        $request = new Request();
        $request->merge(['reports' => $exampleReports]);

        return $this->processReports($request);
    }

    /**
     * Get stored SITREP by incident ID
     */
    public function getSitrep(string $incidentId): JsonResponse
    {
        try {
            $filename = "sitreps/{$incidentId}.json";
            
            if (!Storage::exists($filename)) {
                return response()->json([
                    'error' => 'SITREP not found',
                    'incident_id' => $incidentId,
                ], 404);
            }

            $sitrepData = json_decode(Storage::get($filename), true);

            return response()->json($sitrepData, 200);

        } catch (\Exception $e) {
            Log::error('Error retrieving SITREP', [
                'incident_id' => $incidentId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to retrieve SITREP',
            ], 500);
        }
    }

    /**
     * List all stored SITREPs
     */
    public function listSitreps(): JsonResponse
    {
        try {
            $files = Storage::files('sitreps');
            $sitreps = [];

            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                    $content = json_decode(Storage::get($file), true);
                    $sitreps[] = [
                        'incident_id' => $content['incident_id'],
                        'title' => $content['canonical_title'],
                        'status' => $content['status'],
                        'timestamp' => $content['audit']['created_at'],
                        'location' => $content['location']['name'],
                    ];
                }
            }

            // Sort by timestamp descending
            usort($sitreps, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

            return response()->json([
                'sitreps' => $sitreps,
                'count' => count($sitreps),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error listing SITREPs', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to list SITREPs',
            ], 500);
        }
    }

    /**
     * Select the cluster with highest verification score
     */
    private function selectPrimaryCluster(array $clusters): array
    {
        usort($clusters, fn($a, $b) => $b['verification_score'] <=> $a['verification_score']);
        return $clusters[0];
    }

    /**
     * Apply quality checks as per specification
     */
    private function applyQualityChecks(array $sitrep): array
    {
        // Q1: All three locale summaries exist and are non-empty strings
        foreach (['en', 'hi', 'bn'] as $locale) {
            if (!isset($sitrep['summary'][$locale]) || empty($sitrep['summary'][$locale])) {
                Log::warning('Quality check Q1 failed: Missing summary for locale', ['locale' => $locale]);
                
                // Fallback: translate from English
                if (isset($sitrep['summary']['en']) && $locale !== 'en') {
                    $sitrep['summary'][$locale] = $this->translateFallback($sitrep['summary']['en'], $locale);
                }
            }
        }

        // Q2: Location lat/lng numeric and confidence >= 0.3
        if (!is_numeric($sitrep['location']['lat']) || 
            !is_numeric($sitrep['location']['lng']) || 
            $sitrep['location']['confidence'] < 0.3) {
            
            Log::warning('Quality check Q2 failed: Invalid location data');
            
            $sitrep['location']['lat'] = null;
            $sitrep['location']['lng'] = null;
            $sitrep['location']['confidence'] = 0.0;
            
            // Add details bullet
            $sitrep['details']['bullets_en'][] = 'Location unresolved';
            $sitrep['details']['bullets_hi'][] = 'स्थान अज्ञात';
            $sitrep['details']['bullets_bn'][] = 'অবস্থান অজানা';
        }

        // Q3: Status is present and derived from verification rules
        if (!isset($sitrep['status']) || !in_array($sitrep['status'], ['verified', 'probable', 'unverified'])) {
            Log::warning('Quality check Q3 failed: Invalid status');
            
            $sitrep['status'] = 'unverified';
            $sitrep['recommended_action'] = 'monitor';
        }

        return $sitrep;
    }

    /**
     * Save SITREP to JSON file
     */
    private function saveSitrepToFile(array $sitrep): void
    {
        $filename = "sitreps/{$sitrep['incident_id']}.json";
        Storage::put($filename, json_encode($sitrep, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        // Also save as the main sitrep.json for dashboard
        Storage::put('sitrep.json', json_encode($sitrep, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Fallback translation using basic mappings
     */
    private function translateFallback(string $text, string $targetLocale): string
    {
        // Simple fallback translations for common phrases
        $fallbackTranslations = [
            'hi' => [
                'explosion' => 'धमाका',
                'incident' => 'घटना',
                'reported' => 'रिपोर्ट किया गया',
                'casualties' => 'हताहत',
                'No casualties' => 'कोई हताहत नहीं',
            ],
            'bn' => [
                'explosion' => 'বিস্ফোরণ',
                'incident' => 'ঘটনা',
                'reported' => 'রিপোর্ট করা হয়েছে',
                'casualties' => 'হতাহত',
                'No casualties' => 'কোন হতাহত নেই',
            ],
        ];

        $translated = $text;
        if (isset($fallbackTranslations[$targetLocale])) {
            foreach ($fallbackTranslations[$targetLocale] as $en => $local) {
                $translated = str_ireplace($en, $local, $translated);
            }
        }

        return $translated;
    }
}


// name, location, type, comment, timestamp, 
