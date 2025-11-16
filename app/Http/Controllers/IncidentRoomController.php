<?php

namespace App\Http\Controllers;

use App\Services\LingoSdkService;
use App\Services\GeocodingService;
use App\Services\ReportProcessingPipeline;
use App\Services\SitrepSynthesizerV2;
use App\Services\OpenAIAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class IncidentRoomController extends Controller
{
    private ReportProcessingPipeline $pipeline;
    private SitrepSynthesizerV2 $synthesizerV2;
    private GeocodingService $geocodingService;
    private OpenAIAnalysisService $openaiService;
    private LingoSdkService $lingoService;

    public function __construct(
        LingoSdkService $lingo,
        GeocodingService $geocoding,
        OpenAIAnalysisService $openai
    ) {
        $this->pipeline = new ReportProcessingPipeline($lingo, $geocoding, $openai);
        $this->synthesizerV2 = new SitrepSynthesizerV2($lingo);
        $this->geocodingService = $geocoding;
        $this->openaiService = $openai;
        $this->lingoService = $lingo;
    }

    /**
     * Submit incident form with file uploads
     * NEW WORKFLOW: Upload → Lingo normalize → OpenAI analyze → store canonical EN → Lingo fan-out
     */
    public function submitForm(Request $request): JsonResponse
    {
        // Increase PHP execution time limit for processing
        set_time_limit(300); // 5 minutes
        ini_set('max_execution_time', 300);
        ini_set('memory_limit', '512M');
        ignore_user_abort(true);
        
        try {
            // Log incoming request for debugging
            Log::info('Form submission received', [
                'has_files' => $request->hasFile('images') || $request->hasFile('videos'),
                'all_files' => $request->allFiles(),
            ]);

            // Validate input - now includes incident_title and incident_datetime
            $validated = $request->validate([
                'incident_title' => 'required|string|max:200',
                'raw_text' => 'required|string|max:10000',
                'location' => 'required|string|max:500',
                'original_language' => 'nullable|string',
                'source_credibility' => 'nullable|string',
                'incident_datetime' => 'nullable|string',
                'images' => 'nullable|array',
                'images.*' => 'nullable|file|mimes:jpeg,jpg,png,gif,webp|max:10240', // 10MB max per image
                'videos' => 'nullable|array',
                'videos.*' => 'nullable|file|mimes:mp4,mov,avi,mkv,webm|max:51200', // 50MB max per video
            ]);

            Log::info('NEW WORKFLOW: Processing incident with OpenAI', [
                'title' => $validated['incident_title'],
                'location' => $validated['location']
            ]);

            // Handle file uploads
            $uploadedFiles = $this->handleFileUploads($request);
            
            // Prepare images for OpenAI GPT-4 Vision (base64 encoding)
            $imageData = [];
            if (!empty($uploadedFiles['images'])) {
                foreach ($uploadedFiles['images'] as $image) {
                    try {
                        $imageContent = Storage::get($image['path']);
                        $base64 = base64_encode($imageContent);
                        $imageData[] = [
                            'base64_data' => $base64,
                            'mime_type' => $image['mime_type'],
                            'filename' => $image['original_name']
                        ];
                    } catch (\Exception $e) {
                        Log::warning('Failed to encode image for OpenAI', [
                            'file' => $image['original_name'],
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            // Prepare videos for OpenAI (Whisper + GPT-4 Vision frame analysis)
            $videoData = [];
            if (!empty($uploadedFiles['videos'])) {
                foreach ($uploadedFiles['videos'] as $video) {
                    try {
                        // Get full storage path for local processing
                        $videoPath = Storage::path($video['path']);
                        
                        Log::info('Processing video for OpenAI analysis', [
                            'filename' => $video['original_name'],
                            'mime_type' => $video['mime_type'],
                            'size' => filesize($videoPath)
                        ]);

                        // Store video path for OpenAI service (will extract audio + frames)
                        $videoData[] = [
                            'path' => $videoPath,
                            'mime_type' => $video['mime_type'],
                            'filename' => $video['original_name']
                        ];
                        
                        Log::info('Video ready for OpenAI analysis', [
                            'path' => $videoPath
                        ]);
                    } catch (\Exception $e) {
                        Log::warning('Failed to process video for OpenAI', [
                            'file' => $video['original_name'],
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }

            // Build incident data for new workflow
            $incidentData = [
                'incident_title' => $validated['incident_title'],
                'incident_datetime' => $validated['incident_datetime'] ?? now()->toISOString(),
                'incident_location' => $validated['location'],
                'raw_text' => $validated['raw_text'],
                'original_language' => $validated['original_language'] ?? 'auto',
                'source_credibility' => $validated['source_credibility'] ?? 'medium',
                'images' => $imageData,
                'videos' => $videoData,
                'media_attachments' => $uploadedFiles,
            ];

            // NEW WORKFLOW: Process through OpenAI pipeline
            // Step 1 & 2: Lingo normalize + OpenAI analyze
            $aiReport = $this->pipeline->processIncidentWithAI($incidentData);
            
            Log::info('OpenAI analysis completed', [
                'title' => $aiReport['incident_title'] ?? 'N/A'
            ]);

            // Step 3: Store canonical EN + Lingo fan-out to 2 languages (EN + HI)
            $sitrep = $this->synthesizerV2->synthesizeFromGemini($aiReport);
            
            // Add details bullets for UI compatibility
            $detailsBullets = $this->synthesizerV2->generateDetailsBullets($aiReport);
            $sitrep['details'] = $detailsBullets;

            // Apply quality checks
            $validatedSitrep = $this->applyQualityChecks($sitrep);

            // Save SITREP to file
            $this->saveSitrepToFile($validatedSitrep);

            Log::info('SITREP generated with Gemini + Lingo workflow', [
                'incident_id' => $validatedSitrep['incident_id'],
                'status' => $validatedSitrep['status'],
                'languages' => count($validatedSitrep['summary']),
            ]);

            // Clean UTF-8 data before returning JSON response
            $cleanedSitrep = $this->cleanUtf8Data($validatedSitrep);
            
            return response()->json($cleanedSitrep, 200, [], JSON_UNESCAPED_UNICODE);

        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed for form submission', [
                'errors' => $e->errors(),
                'has_images' => $request->hasFile('images'),
                'has_videos' => $request->hasFile('videos'),
                'image_count' => count($request->file('images') ?? []),
                'video_count' => count($request->file('videos') ?? []),
            ]);
            
            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors(),
                'message' => 'Please check your input and uploaded files',
            ], 422);

        } catch (\Exception $e) {
            Log::error('Error processing form submission', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Internal processing error',
                'message' => 'Failed to process incident form: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle file uploads and store them
     */
    private function handleFileUploads(Request $request): array
    {
        $uploadedFiles = [
            'images' => [],
            'videos' => []
        ];

        // Handle image uploads
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $file) {
                if ($file->isValid()) {
                    $filename = 'incident_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('incidents/images', $filename, 'public');
                    
                    $uploadedFiles['images'][] = [
                        'original_name' => $file->getClientOriginalName(),
                        'stored_name' => $filename,
                        'path' => $path,
                        'url' => asset('storage/' . $path),
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ];
                }
            }
        }

        // Handle video uploads
        if ($request->hasFile('videos')) {
            foreach ($request->file('videos') as $file) {
                if ($file->isValid()) {
                    $filename = 'incident_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
                    $path = $file->storeAs('incidents/videos', $filename, 'public');
                    
                    $uploadedFiles['videos'][] = [
                        'original_name' => $file->getClientOriginalName(),
                        'stored_name' => $filename,
                        'path' => $path,
                        'url' => asset('storage/' . $path),
                        'size' => $file->getSize(),
                        'mime_type' => $file->getMimeType(),
                    ];
                }
            }
        }

        return $uploadedFiles;
    }

    /**
     * Process incident reports and generate SITREP
     */
    public function getSitrep(string $incidentId): JsonResponse
    {
        try {
            $filename = "sitreps/{$incidentId}.json";
            
            if (!Storage::disk('local')->exists($filename)) {
                return response()->json([
                    'error' => 'SITREP not found',
                    'incident_id' => $incidentId,
                ], 404);
            }

            $sitrepData = json_decode(Storage::disk('local')->get($filename), true);

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
            $files = Storage::disk('local')->files('sitreps');
            $sitreps = [];

            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'json') {
                    try {
                        $content = json_decode(Storage::disk('local')->get($file), true);
                        if ($content && isset($content['incident_id'])) {
                            $sitreps[] = [
                                'incident_id' => $content['incident_id'],
                                'title' => $content['canonical_title'] ?? 'Untitled Incident',
                                'status' => $content['status'] ?? 'unknown',
                                'timestamp' => $content['audit']['created_at'] ?? $content['time_window']['first_report'] ?? now()->toISOString(),
                                'location' => $content['location']['name'] ?? 'Unknown Location',
                            ];
                        }
                    } catch (\Exception $fileError) {
                        Log::warning("Error reading SITREP file: {$file}", [
                            'error' => $fileError->getMessage()
                        ]);
                        continue;
                    }
                }
            }

            // Sort by timestamp descending
            usort($sitreps, function($a, $b) {
                return strtotime($b['timestamp']) <=> strtotime($a['timestamp']);
            });

            Log::info('Listed SITREPs', [
                'count' => count($sitreps),
                'files_found' => count($files)
            ]);

            return response()->json([
                'sitreps' => $sitreps,
                'count' => count($sitreps),
            ], 200);

        } catch (\Exception $e) {
            Log::error('Error listing SITREPs', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Failed to list SITREPs',
                'debug' => $e->getMessage(),
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
        // Clean the data to ensure valid UTF-8
        $cleanedSitrep = $this->cleanUtf8Data($sitrep);
        
        $filename = "sitreps/{$cleanedSitrep['incident_id']}.json";
        
        // Encode with error handling
        $jsonData = json_encode($cleanedSitrep, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        
        if ($jsonData === false) {
            $error = json_last_error_msg();
            Log::error('JSON encoding failed for SITREP', [
                'incident_id' => $cleanedSitrep['incident_id'],
                'error' => $error
            ]);
            throw new \RuntimeException("Failed to encode SITREP as JSON: {$error}");
        }
        
        Storage::disk('local')->put($filename, $jsonData);
        
        // Also save as the main sitrep.json for dashboard
        Storage::disk('local')->put('sitrep.json', $jsonData);
        
        Log::info('SITREP saved to file', [
            'incident_id' => $cleanedSitrep['incident_id'],
            'filename' => $filename
        ]);
    }
    
    /**
     * Recursively clean UTF-8 data in arrays and strings
     */
    private function cleanUtf8Data($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'cleanUtf8Data'], $data);
        }
        
        if (is_string($data)) {
            // Remove invalid UTF-8 characters
            $data = mb_convert_encoding($data, 'UTF-8', 'UTF-8');
            // Alternative: use iconv for stricter cleaning
            // $data = iconv('UTF-8', 'UTF-8//IGNORE', $data);
        }
        
        return $data;
    }

    /**
     * Fallback translation - just return original text since Lingo SDK should handle all translations
     */
    private function translateFallback(string $text, string $targetLocale): string
    {
        // No static translations - rely on Lingo SDK for all translation work
        Log::warning("Translation fallback triggered - Lingo SDK should handle this", [
            'text' => $text,
            'target_locale' => $targetLocale
        ]);
        
        return $text; // Return original text if Lingo fails
    }
} 
