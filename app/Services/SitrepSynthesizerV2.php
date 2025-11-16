<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

/**
 * SitrepSynthesizerV2
 * 
 * NEW VERSION for Gemini-analyzed incident reports
 * Workflow: Canonical EN report from Gemini → Lingo fan-out to 10+ languages
 */
class SitrepSynthesizerV2
{
    private LingoSdkService $lingo;

    public function __construct(LingoSdkService $lingo)
    {
        $this->lingo = $lingo;
    }

    /**
     * Synthesize SITREP from Gemini-analyzed incident report
     * 
     * @param array $geminiReport Canonical English report from Gemini
     * @return array Complete SITREP with multilingual translations
     */
    public function synthesizeFromGemini(array $geminiReport): array
    {
        Log::info('Synthesizing SITREP from Gemini report');
        
        // Generate unique incident ID
        $incidentId = $this->generateIncidentId($geminiReport);
        
        // Extract core information from Gemini report
        $title = $geminiReport['incident_title'] ?? 'Incident Report';
        $location = $this->extractLocation($geminiReport);
        $timeWindow = $this->extractTimeWindow($geminiReport);
        
        // Translate summary to 10+ languages (Lingo fan-out)
        $summary = $this->generateMultilingualSummary($geminiReport['summary'] ?? '');
        
        // Translate description to 10+ languages
        $description = $this->generateMultilingualDescription($geminiReport['description'] ?? '');
        
        // Format people involved sections
        $peopleInvolved = $this->formatPeopleInvolved($geminiReport['people_involved'] ?? []);
        
        // Format actions taken sections
        $actionsTaken = $this->formatActionsTaken($geminiReport['actions_taken'] ?? []);
        
        // Determine verification status
        $status = $this->determineVerificationStatus($geminiReport);
        
        // Build complete SITREP
        $sitrep = [
            'incident_id' => $incidentId,
            'canonical_title' => $title,
            'location' => $location,
            'time_window' => $timeWindow,
            'status' => $status,
            
            // Multilingual summary (13 languages)
            'summary' => $summary,
            
            // Multilingual description (13 languages)
            'description' => $description,
            
            // People involved (multilingual)
            'people_involved' => $peopleInvolved,
            
            // Actions taken (multilingual)
            'actions_taken' => $actionsTaken,
            
            // Source information
            'sources' => [
                'report_count' => $this->countEvidenceSources($geminiReport),
                'report_ids' => [$geminiReport['incident_title'] ?? 'gemini_report'],
                'top_3_sources_summary' => $this->inferSourceTypes($geminiReport),
                'credibility' => $geminiReport['source_credibility'] ?? 'medium',
                'reports' => $this->buildReportsSummary($geminiReport),
            ],
            
            // Media attachments
            'media_attachments' => $geminiReport['media_attachments'] ?? [],
            
            // Recommended action
            'recommended_action' => $this->determineRecommendedAction($geminiReport, $status),
            
            // Audit trail
            'audit' => [
                'gemini_analysis_used' => true,
                'is_fallback' => $geminiReport['is_fallback'] ?? false,
                'raw_gemini_response' => $geminiReport['raw_gemini_response'] ?? '',
                'created_at' => now()->toISOString(),
            ],
        ];
        
        Log::info('SITREP synthesized successfully', [
            'incident_id' => $incidentId,
            'status' => $status
        ]);
        
        return $sitrep;
    }

    /**
     * Generate unique incident ID
     */
    private function generateIncidentId(array $geminiReport): string
    {
        $timestamp = Carbon::now()->format('Ymd_His');
        $title = $geminiReport['incident_title'] ?? 'incident';
        $titleSlug = Str::slug(substr($title, 0, 30), '_');
        $hash = substr(md5(json_encode($geminiReport)), 0, 6);
        
        return sprintf('%s_%s_%s', $timestamp, $titleSlug, $hash);
    }

    /**
     * Extract location information
     */
    private function extractLocation(array $geminiReport): array
    {
        $geocoded = $geminiReport['geocoded_location'] ?? null;
        
        if ($geocoded && isset($geocoded['lat']) && $geocoded['lat'] !== null) {
            return [
                'name' => $geminiReport['location'] ?? $geocoded['display_name'] ?? 'Unknown',
                'lat' => $geocoded['lat'],
                'lng' => $geocoded['lng'],
                'confidence' => $geocoded['confidence'] ?? 0.5,
            ];
        }
        
        return [
            'name' => $geminiReport['location'] ?? 'Location not specified',
            'lat' => null,
            'lng' => null,
            'confidence' => 0.0,
        ];
    }

    /**
     * Extract time window information
     */
    private function extractTimeWindow(array $geminiReport): array
    {
        $dateTime = $geminiReport['date_time'] ?? 'Not specified';
        $now = now();
        
        // Parse datetime from Gemini or use current time
        if ($dateTime !== 'Not specified' && !str_contains($dateTime, 'Not specified')) {
            try {
                $eventTime = Carbon::parse($dateTime);
            } catch (\Exception $e) {
                $eventTime = $now;
            }
        } else {
            $eventTime = $now;
        }
        
        return [
            'first_report' => $eventTime->toISOString(),
            'last_report' => $eventTime->toISOString(),
            'approx_event_time' => $eventTime->toISOString(),
            'time_confidence' => ($dateTime !== 'Not specified') ? 0.8 : 0.3,
        ];
    }

    /**
     * Generate multilingual summary (English + Hindi only)
     */
    private function generateMultilingualSummary(string $englishSummary): array
    {
        if (empty($englishSummary)) {
            $englishSummary = 'No summary available';
        }
        
        // Clean the text before translation (remove any encoded data)
        $cleanedSummary = $this->cleanTextForTranslation($englishSummary);
        
        try {
            $translations = $this->lingo->compile($cleanedSummary, ['hi']);
            
            return [
                'en' => $cleanedSummary,
                'hi' => $translations['hi'] ?? $cleanedSummary
            ];
        } catch (\Exception $e) {
            Log::warning('Translation failed for summary', ['error' => $e->getMessage()]);
            return [
                'en' => $cleanedSummary,
                'hi' => $cleanedSummary // Fallback to English
            ];
        }
    }

    /**
     * Generate multilingual description (English + Hindi only)
     */
    private function generateMultilingualDescription(string $englishDescription): array
    {
        if (empty($englishDescription)) {
            $englishDescription = 'No detailed description available';
        }
        
        // Clean the text before translation
        $cleanedDescription = $this->cleanTextForTranslation($englishDescription);
        
        try {
            $translations = $this->lingo->compile($cleanedDescription, ['hi']);
            
            return [
                'en' => $cleanedDescription,
                'hi' => $translations['hi'] ?? $cleanedDescription
            ];
        } catch (\Exception $e) {
            Log::warning('Translation failed for description', ['error' => $e->getMessage()]);
            return [
                'en' => $cleanedDescription,
                'hi' => $cleanedDescription
            ];
        }
    }

    /**
     * Clean text before sending to translation API
     * Remove base64 encoded data, excessive whitespace, etc.
     */
    private function cleanTextForTranslation(string $text): string
    {
        // Remove any base64 encoded content (images/videos)
        $text = preg_replace('/data:image\/[^;]+;base64,[A-Za-z0-9+\/=]+/', '[IMAGE]', $text);
        $text = preg_replace('/data:video\/[^;]+;base64,[A-Za-z0-9+\/=]+/', '[VIDEO]', $text);
        
        // Remove excessively long base64 strings
        $text = preg_replace('/[A-Za-z0-9+\/=]{100,}/', '', $text);
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Trim
        $text = trim($text);
        
        // Limit length to prevent API issues
        if (strlen($text) > 5000) {
            $text = substr($text, 0, 5000) . '...';
        }
        
        return $text;
    }

    /**
     * Format people involved with multilingual support (English + Hindi)
     */
    private function formatPeopleInvolved(array $peopleData): array
    {
        $formatted = [
            'victims' => [],
            'suspects' => [],
            'witnesses' => []
        ];
        
        foreach (['victims', 'suspects', 'witnesses'] as $category) {
            $items = $peopleData[$category] ?? [];
            
            foreach ($items as $item) {
                // Handle both OpenAI format (object with name/details) and old format (string)
                if (is_array($item)) {
                    $name = $item['name'] ?? '';
                    $details = $item['details'] ?? '';
                    $fullText = trim($name . ($details ? ': ' . $details : ''));
                } else {
                    $fullText = (string)$item;
                }
                
                // Clean and translate each item
                $cleanedItem = $this->cleanTextForTranslation($fullText);
                
                try {
                    $translations = $this->lingo->compile($cleanedItem, ['hi']);
                    
                    $formatted[$category][] = [
                        'en' => $cleanedItem,
                        'hi' => $translations['hi'] ?? $cleanedItem
                    ];
                } catch (\Exception $e) {
                    Log::warning('Translation failed for people involved', [
                        'category' => $category,
                        'error' => $e->getMessage()
                    ]);
                    $formatted[$category][] = [
                        'en' => $cleanedItem,
                        'hi' => $cleanedItem
                    ];
                }
            }
            
            // If empty, add "Not specified"
            if (empty($formatted[$category])) {
                try {
                    $notSpecified = $this->lingo->compile('Not specified', ['hi']);
                    $formatted[$category][] = [
                        'en' => 'Not specified',
                        'hi' => $notSpecified['hi'] ?? 'Not specified'
                    ];
                } catch (\Exception $e) {
                    $formatted[$category][] = [
                        'en' => 'Not specified',
                        'hi' => 'Not specified'
                    ];
                }
            }
        }
        
        return $formatted;
    }

    /**
     * Format actions taken with multilingual support (English + Hindi)
     */
    private function formatActionsTaken(array $actionsData): array
    {
        $formatted = [
            'emergency_response' => [],
            'police_actions' => [],
            'medical_interventions' => []
        ];
        
        foreach (['emergency_response', 'police_actions', 'medical_interventions'] as $category) {
            $items = $actionsData[$category] ?? [];
            
            foreach ($items as $item) {
                // Clean and translate each action
                $cleanedItem = $this->cleanTextForTranslation($item);
                
                try {
                    $translations = $this->lingo->compile($cleanedItem, ['hi']);
                    
                    $formatted[$category][] = [
                        'en' => $cleanedItem,
                        'hi' => $translations['hi'] ?? $cleanedItem
                    ];
                } catch (\Exception $e) {
                    Log::warning('Translation failed for actions taken', [
                        'category' => $category,
                        'error' => $e->getMessage()
                    ]);
                    $formatted[$category][] = [
                        'en' => $cleanedItem,
                        'hi' => $cleanedItem
                    ];
                }
            }
            
            // If empty, add "Not specified"
            if (empty($formatted[$category])) {
                try {
                    $notSpecified = $this->lingo->compile('Not specified', ['hi']);
                    $formatted[$category][] = [
                        'en' => 'Not specified',
                        'hi' => $notSpecified['hi'] ?? 'Not specified'
                    ];
                } catch (\Exception $e) {
                    $formatted[$category][] = [
                        'en' => 'Not specified',
                        'hi' => 'Not specified'
                    ];
                }
            }
        }
        
        return $formatted;
    }

    /**
     * Determine verification status based on Gemini analysis
     */
    private function determineVerificationStatus(array $geminiReport): string
    {
        // Check if this is a fallback report
        if ($geminiReport['is_fallback'] ?? false) {
            return 'unverified';
        }
        
        // Check source credibility
        $credibility = $geminiReport['source_credibility'] ?? 'unknown';
        
        if (in_array($credibility, ['official', 'verified', 'high'])) {
            return 'verified';
        }
        
        if ($credibility === 'medium') {
            return 'probable';
        }
        
        return 'unverified';
    }

    /**
     * Infer source types from media attachments
     */
    /**
     * Count total evidence sources (text + images + videos)
     */
    private function countEvidenceSources(array $geminiReport): int
    {
        $count = 1; // Always at least the text description
        
        $mediaAttachments = $geminiReport['media_attachments'] ?? [];
        
        // Count images as evidence sources
        if (!empty($mediaAttachments['images'])) {
            $count += count($mediaAttachments['images']);
        }
        
        // Count videos as evidence sources
        if (!empty($mediaAttachments['videos'])) {
            $count += count($mediaAttachments['videos']);
        }
        
        return $count;
    }

    /**
     * Build detailed reports summary for source information
     */
    private function buildReportsSummary(array $geminiReport): array
    {
        $reports = [];
        
        // Add text report
        $reports[] = [
            'report_id' => 'text_description',
            'raw_text' => $geminiReport['description'] ?? '',
            'original_language' => $geminiReport['original_language'] ?? 'en',
            'source_type' => 'text_report',
            'credibility' => $geminiReport['source_credibility'] ?? 'medium',
            'timestamp' => $geminiReport['date_time'] ?? now()->toISOString(),
        ];
        
        $mediaAttachments = $geminiReport['media_attachments'] ?? [];
        
        // Add image reports
        if (!empty($mediaAttachments['images'])) {
            foreach ($mediaAttachments['images'] as $index => $image) {
                $reports[] = [
                    'report_id' => 'image_' . ($index + 1),
                    'raw_text' => 'Visual evidence from uploaded image',
                    'source_type' => 'image_evidence',
                    'credibility' => 'high',
                    'media_type' => 'image',
                    'filename' => $image['original_name'] ?? 'image_' . ($index + 1),
                ];
            }
        }
        
        // Add video reports
        if (!empty($mediaAttachments['videos'])) {
            foreach ($mediaAttachments['videos'] as $index => $video) {
                $reports[] = [
                    'report_id' => 'video_' . ($index + 1),
                    'raw_text' => 'Visual evidence from uploaded video',
                    'source_type' => 'video_evidence',
                    'credibility' => 'high',
                    'media_type' => 'video',
                    'filename' => $video['original_name'] ?? 'video_' . ($index + 1),
                ];
            }
        }
        
        return $reports;
    }

    private function inferSourceTypes(array $geminiReport): array
    {
        $sources = [];
        
        // Check media attachments
        $mediaAttachments = $geminiReport['media_attachments'] ?? [];
        
        if (!empty($mediaAttachments['images'])) {
            $sources[] = 'image_evidence';
        }
        
        if (!empty($mediaAttachments['videos'])) {
            $sources[] = 'video_evidence';
        }
        
        // Always has text
        $sources[] = 'text_report';
        
        return array_unique($sources);
    }

    /**
     * Determine recommended action
     */
    private function determineRecommendedAction(array $geminiReport, string $status): string
    {
        // If verified and has serious event indicators, alert authorities
        if ($status === 'verified') {
            $summary = strtolower($geminiReport['summary'] ?? '');
            
            if (str_contains($summary, 'explosion') || 
                str_contains($summary, 'fire') || 
                str_contains($summary, 'collapse') ||
                str_contains($summary, 'shooting')) {
                return 'alert_authorities';
            }
            
            return 'publish';
        }
        
        if ($status === 'probable') {
            return 'request_verification';
        }
        
        return 'monitor';
    }

    /**
     * Generate details bullets (for backward compatibility)
     * This creates bullet points from the description
     */
    public function generateDetailsBullets(array $geminiReport): array
    {
        $description = $geminiReport['description'] ?? '';
        
        // Split description into sentences for bullets
        $sentences = preg_split('/[.!?]+/', $description);
        $bullets = array_filter(array_map('trim', $sentences));
        $bullets = array_slice($bullets, 0, 5); // Max 5 bullets
        
        // Start with English bullets
        $bulletsByLang = ['en' => $bullets, 'hi' => []];
        
        // Translate to Hindi only
        try {
            foreach ($bullets as $bullet) {
                $cleanedBullet = $this->cleanTextForTranslation($bullet);
                $translations = $this->lingo->compile($cleanedBullet, ['hi']);
                $bulletsByLang['hi'][] = $translations['hi'] ?? $cleanedBullet;
            }
        } catch (\Exception $e) {
            Log::warning('Translation failed for bullets', ['error' => $e->getMessage()]);
            $bulletsByLang['hi'] = $bullets; // Fallback to English
        }
        
        return [
            'bullets_en' => $bulletsByLang['en'],
            'bullets_hi' => $bulletsByLang['hi']
        ];
    }
}
