<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ReportProcessingPipeline
{
    private LingoSdkService $lingo;
    private GeocodingService $geocoding;
    private OpenAIAnalysisService $openai;

    public function __construct(
        LingoSdkService $lingo, 
        GeocodingService $geocoding,
        OpenAIAnalysisService $openai
    ) {
        $this->lingo = $lingo;
        $this->geocoding = $geocoding;
        $this->openai = $openai;
    }

    /**
     * NEW WORKFLOW: Upload → Lingo normalize → OpenAI analyze → store canonical EN
     * Process a single incident report with multimodal evidence
     * 
     * @param array $incidentData Contains:
     *   - incident_title: string
     *   - incident_datetime: string (ISO 8601)
     *   - incident_location: string
     *   - raw_text: string
     *   - original_language: string
     *   - images: array of image data
     *   - videos: array of video data
     *   - source_credibility: string
     * 
     * @return array OpenAI-analyzed canonical English report
     */
    public function processIncidentWithAI(array $incidentData): array
    {
        Log::info('Processing incident with OpenAI workflow', [
            'title' => $incidentData['incident_title'] ?? 'N/A',
            'location' => $incidentData['incident_location'] ?? 'N/A'
        ]);

        // Step 1: Normalize text content with Lingo
        $normalizedEvidence = $this->normalizeEvidenceWithLingo($incidentData);
        
        // Step 2: Analyze with OpenAI (GPT-4 Vision + Whisper)
        $aiReport = $this->openai->analyzeIncident($normalizedEvidence);
        
        // Step 3: Enrich with geo data
        $enrichedReport = $this->enrichWithGeocoding($aiReport, $incidentData);
        
        // Step 4: Add metadata
        $enrichedReport['source_credibility'] = $incidentData['source_credibility'] ?? 'unknown';
        $enrichedReport['original_language'] = $incidentData['original_language'] ?? 'auto';
        $enrichedReport['media_attachments'] = [
            'images' => $incidentData['media_attachments']['images'] ?? [],
            'videos' => $incidentData['media_attachments']['videos'] ?? []
        ];
        
        return $enrichedReport;
    }

    /**
     * Normalize evidence using Lingo before AI analysis
     */
    private function normalizeEvidenceWithLingo(array $incidentData): array
    {
        $evidence = [
            'incident_title' => $incidentData['incident_title'] ?? '',
            'incident_datetime' => $incidentData['incident_datetime'] ?? '',
            'incident_location' => $incidentData['incident_location'] ?? '',
            'text_evidence' => [],
            'image_evidence' => [],
            'video_evidence' => [],
            'metadata' => []
        ];

        // Normalize text description
        if (!empty($incidentData['raw_text'])) {
            $text = $incidentData['raw_text'];
            $originalLang = $incidentData['original_language'] ?? 'auto';
            
            // Detect language if auto
            if ($originalLang === 'auto') {
                $originalLang = $this->lingo->detectLanguage($text);
            }
            
            // Normalize text (clean filler words, fix encoding)
            $text = $this->normalizeText($text);
            
            // Translate to English for AI analysis
            if ($originalLang !== 'en') {
                try {
                    $translatedText = $this->lingo->translate($text, $originalLang, 'en');
                    $evidence['text_evidence'][] = $translatedText;
                    $evidence['metadata']['translation'] = [
                        'original_language' => $originalLang,
                        'original_text' => $text,
                        'translated_text' => $translatedText
                    ];
                } catch (\Exception $e) {
                    Log::warning('Lingo translation failed, using original text', [
                        'error' => $e->getMessage()
                    ]);
                    $evidence['text_evidence'][] = $text;
                }
            } else {
                $evidence['text_evidence'][] = $text;
            }
        }

        // Add image evidence (base64 encoded)
        if (!empty($incidentData['images'])) {
            foreach ($incidentData['images'] as $image) {
                $evidence['image_evidence'][] = [
                    'data' => $image['base64_data'],
                    'mime_type' => $image['mime_type'] ?? 'image/jpeg',
                    'filename' => $image['filename'] ?? 'unknown.jpg'
                ];
            }
        }

        // Add video evidence (file paths for OpenAI processing)
        if (!empty($incidentData['videos'])) {
            foreach ($incidentData['videos'] as $video) {
                $evidence['video_evidence'][] = [
                    'path' => $video['path'] ?? '',
                    'filename' => $video['filename'] ?? 'unknown.mp4'
                ];
            }
        }

        return $evidence;
    }

    /**
     * Normalize text by removing filler words and fixing encoding
     */
    private function normalizeText(string $text): string
    {
        // Remove filler tokens common in voice transcripts
        $fillerWords = ['uh', 'um', 'basically', 'you know', 'like', 'so', 'actually'];
        foreach ($fillerWords as $filler) {
            $text = preg_replace('/\b' . preg_quote($filler, '/') . '\b/i', '', $text);
        }
        
        // Fix encoding issues
        $text = mb_convert_encoding($text, 'UTF-8', 'auto');
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        // Remove excessive punctuation
        $text = preg_replace('/[।.]{3,}/', '.', $text);
        $text = preg_replace('/[!]{2,}/', '!', $text);
        
        return trim($text);
    }

    /**
     * Enrich AI report with geocoding data
     */
    private function enrichWithGeocoding(array $aiReport, array $incidentData): array
    {
        // Use user-provided location or AI-extracted location
        $locationString = $incidentData['incident_location'] ?? $aiReport['location'] ?? '';
        
        if (!empty($locationString)) {
            try {
                $geotag = $this->geocoding->resolve($locationString);
                $aiReport['geocoded_location'] = $geotag;
            } catch (\Exception $e) {
                Log::warning('Geocoding failed', [
                    'location' => $locationString,
                    'error' => $e->getMessage()
                ]);
                $aiReport['geocoded_location'] = [
                    'lat' => null,
                    'lng' => null,
                    'confidence' => 0.0,
                    'source' => 'geocoding_failed'
                ];
            }
        }
        
        return $aiReport;
    }
}
