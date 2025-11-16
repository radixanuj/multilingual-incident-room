<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use OpenAI;

/**
 * OpenAIAnalysisService
 * 
 * AI-powered incident analysis using OpenAI's GPT-4 Vision and Whisper APIs
 * Processes multimodal evidence:
 * - Text descriptions
 * - Images (analyzed via GPT-4 Vision)
 * - Videos (audio extracted via FFmpeg → Whisper, frames → GPT-4 Vision)
 * 
 * Generates structured incident reports for law enforcement
 */
class OpenAIAnalysisService
{
    private $client;
    private string $visionModel = 'gpt-4-turbo';
    private string $whisperModel = 'whisper-1';
    private int $timeout = 180; // 3 minutes for video processing
    private string $tempDir;

    public function __construct()
    {
        $apiKey = config('services.openai.api_key');
        
        if (empty($apiKey)) {
            throw new \Exception('OpenAI API key not configured. Please set OPENAI_API_KEY in .env');
        }

        $this->client = OpenAI::client($apiKey);
        $this->tempDir = storage_path('app/temp');
        
        // Ensure temp directory exists
        if (!file_exists($this->tempDir)) {
            mkdir($this->tempDir, 0755, true);
        }
    }

    /**
     * Analyze incident evidence and generate structured report
     * 
     * @param array $evidence Array containing:
     *   - incident_title: string
     *   - incident_datetime: string (ISO 8601)
     *   - incident_location: string
     *   - text_evidence: array of strings
     *   - image_evidence: array of base64 encoded images
     *   - video_evidence: array of video file paths
     * 
     * @return array Structured incident report
     */
    public function analyzeIncident(array $evidence): array
    {
        Log::info('OpenAIAnalysisService: Starting incident analysis', [
            'has_title' => !empty($evidence['incident_title']),
            'has_datetime' => !empty($evidence['incident_datetime']),
            'location' => $evidence['incident_location'] ?? 'Not provided',
            'text_count' => count($evidence['text_evidence'] ?? []),
            'image_count' => count($evidence['image_evidence'] ?? []),
            'video_count' => count($evidence['video_evidence'] ?? []),
        ]);

        try {
            // Process all evidence types
            $textContent = $this->processTextEvidence($evidence['text_evidence'] ?? []);
            $imageAnalysis = $this->processImageEvidence($evidence['image_evidence'] ?? []);
            $videoAnalysis = $this->processVideoEvidence($evidence['video_evidence'] ?? []);

            // Combine all analysis
            $combinedEvidence = $this->combineEvidence($textContent, $imageAnalysis, $videoAnalysis);

            // Generate structured report using GPT-4
            $structuredReport = $this->generateStructuredReport($evidence, $combinedEvidence);

            Log::info('OpenAI analysis completed successfully');

            return $structuredReport;

        } catch (\Exception $e) {
            Log::error('OpenAI analysis failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            // Return fallback report
            return $this->generateFallbackReport($evidence);
        }
    }

    /**
     * Process text evidence
     */
    private function processTextEvidence(array $textEvidence): string
    {
        return implode("\n\n", $textEvidence);
    }

    /**
     * Process image evidence using GPT-4 Vision
     */
    private function processImageEvidence(array $imageEvidence): array
    {
        if (empty($imageEvidence)) {
            return [];
        }

        Log::info('Processing images with GPT-4 Vision', ['count' => count($imageEvidence)]);

        $imageAnalyses = [];

        foreach ($imageEvidence as $index => $image) {
            try {
                $base64Data = $image['data'] ?? '';
                $mimeType = $image['mime_type'] ?? 'image/jpeg';

                if (empty($base64Data)) {
                    continue;
                }

                $response = $this->client->chat()->create([
                    'model' => $this->visionModel,
                    'messages' => [
                        [
                            'role' => 'user',
                            'content' => [
                                [
                                    'type' => 'text',
                                    'text' => 'You are analyzing evidence for a law enforcement incident report. Describe this image in detail, focusing on: people visible (appearances, actions), objects/weapons, vehicles, damage/injuries, location details, visible text/signs, time indicators (clocks, lighting), and any other relevant evidence. Be factual and specific.'
                                ],
                                [
                                    'type' => 'image_url',
                                    'image_url' => [
                                        'url' => "data:{$mimeType};base64,{$base64Data}",
                                        'detail' => 'high'
                                    ]
                                ]
                            ]
                        ]
                    ],
                    'max_tokens' => 500
                ]);

                $analysis = $response->choices[0]->message->content;
                $imageAnalyses[] = "Image " . ($index + 1) . ": " . $analysis;

                Log::info('Image analyzed successfully', ['image_index' => $index + 1]);

            } catch (\Exception $e) {
                Log::warning('Failed to analyze image', [
                    'image_index' => $index + 1,
                    'error' => $e->getMessage()
                ]);
                $imageAnalyses[] = "Image " . ($index + 1) . ": Analysis failed";
            }
        }

        return $imageAnalyses;
    }

    /**
     * Process video evidence: extract audio + frames, analyze both
     */
    private function processVideoEvidence(array $videoEvidence): array
    {
        if (empty($videoEvidence)) {
            return [];
        }

        Log::info('Processing videos', ['count' => count($videoEvidence)]);

        $videoAnalyses = [];

        foreach ($videoEvidence as $index => $video) {
            try {
                $videoPath = $video['path'] ?? '';
                
                if (empty($videoPath) || !file_exists($videoPath)) {
                    continue;
                }

                // Extract and transcribe audio
                $transcript = $this->extractAndTranscribeAudio($videoPath, $index);

                // Extract and analyze key frames
                $frameAnalysis = $this->extractAndAnalyzeFrames($videoPath, $index);

                // Combine analysis
                $analysis = "Video " . ($index + 1) . ":\n";
                if (!empty($transcript)) {
                    $analysis .= "Audio Transcript: " . $transcript . "\n";
                }
                if (!empty($frameAnalysis)) {
                    $analysis .= "Visual Content: " . implode(" ", $frameAnalysis);
                }

                $videoAnalyses[] = $analysis;

                Log::info('Video analyzed successfully', ['video_index' => $index + 1]);

            } catch (\Exception $e) {
                Log::warning('Failed to analyze video', [
                    'video_index' => $index + 1,
                    'error' => $e->getMessage()
                ]);
                $videoAnalyses[] = "Video " . ($index + 1) . ": Analysis failed";
            }
        }

        return $videoAnalyses;
    }

    /**
     * Extract audio from video and transcribe using Whisper
     */
    private function extractAndTranscribeAudio(string $videoPath, int $index): string
    {
        $audioPath = $this->tempDir . "/audio_{$index}_" . uniqid() . ".mp3";

        try {
            // Extract audio using FFmpeg
            $command = "ffmpeg -i " . escapeshellarg($videoPath) . " -vn -acodec libmp3lame -q:a 4 " . escapeshellarg($audioPath) . " 2>&1";
            exec($command, $output, $returnCode);

            if ($returnCode !== 0 || !file_exists($audioPath)) {
                Log::warning('FFmpeg audio extraction failed', [
                    'video_index' => $index,
                    'command' => $command,
                    'output' => implode("\n", $output)
                ]);
                return '';
            }

            // Check if audio file has content
            if (filesize($audioPath) < 1024) { // Less than 1KB, probably no audio
                unlink($audioPath);
                return '(No audio detected)';
            }

            // Transcribe using Whisper
            $response = $this->client->audio()->transcribe([
                'model' => $this->whisperModel,
                'file' => fopen($audioPath, 'r'),
                'language' => 'en', // Can be auto-detected by removing this
            ]);

            $transcript = $response->text ?? '';

            // Cleanup
            unlink($audioPath);

            return $transcript;

        } catch (\Exception $e) {
            Log::warning('Audio transcription failed', [
                'video_index' => $index,
                'error' => $e->getMessage()
            ]);
            
            // Cleanup on error
            if (file_exists($audioPath)) {
                unlink($audioPath);
            }
            
            return '';
        }
    }

    /**
     * Extract key frames from video and analyze using GPT-4 Vision
     */
    private function extractAndAnalyzeFrames(string $videoPath, int $index): array
    {
        $frameAnalyses = [];
        $frameDir = $this->tempDir . "/frames_{$index}_" . uniqid();
        mkdir($frameDir, 0755, true);

        try {
            // Extract 3 frames: beginning, middle, end (every 33% of duration)
            $command = "ffmpeg -i " . escapeshellarg($videoPath) . " -vf \"select='not(mod(n\\,round(n/3)))',scale=1024:-1\" -vsync vfr -frames:v 3 " . escapeshellarg($frameDir . "/frame_%03d.jpg") . " 2>&1";
            exec($command, $output, $returnCode);

            if ($returnCode !== 0) {
                Log::warning('FFmpeg frame extraction failed', [
                    'video_index' => $index,
                    'output' => implode("\n", $output)
                ]);
                return [];
            }

            // Analyze each extracted frame
            $frames = glob($frameDir . "/frame_*.jpg");
            
            foreach (array_slice($frames, 0, 3) as $frameIndex => $framePath) {
                try {
                    $base64Image = base64_encode(file_get_contents($framePath));

                    $response = $this->client->chat()->create([
                        'model' => $this->visionModel,
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => [
                                    [
                                        'type' => 'text',
                                        'text' => 'Describe what is happening in this video frame. Focus on: people, actions, objects, location, any visible damage or injuries. Be concise.'
                                    ],
                                    [
                                        'type' => 'image_url',
                                        'image_url' => [
                                            'url' => "data:image/jpeg;base64,{$base64Image}",
                                            'detail' => 'low' // Use low detail for video frames
                                        ]
                                    ]
                                ]
                            ]
                        ],
                        'max_tokens' => 200
                    ]);

                    $frameAnalyses[] = $response->choices[0]->message->content;

                } catch (\Exception $e) {
                    Log::warning('Frame analysis failed', [
                        'video_index' => $index,
                        'frame_index' => $frameIndex,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // Cleanup frames
            array_map('unlink', glob($frameDir . "/*"));
            rmdir($frameDir);

        } catch (\Exception $e) {
            Log::warning('Frame extraction and analysis failed', [
                'video_index' => $index,
                'error' => $e->getMessage()
            ]);
            
            // Cleanup on error
            if (is_dir($frameDir)) {
                array_map('unlink', glob($frameDir . "/*") ?: []);
                rmdir($frameDir);
            }
        }

        return $frameAnalyses;
    }

    /**
     * Combine all evidence into a coherent narrative
     */
    private function combineEvidence(string $textContent, array $imageAnalysis, array $videoAnalysis): string
    {
        $combined = "=== TEXT EVIDENCE ===\n" . $textContent . "\n\n";

        if (!empty($imageAnalysis)) {
            $combined .= "=== IMAGE ANALYSIS ===\n" . implode("\n\n", $imageAnalysis) . "\n\n";
        }

        if (!empty($videoAnalysis)) {
            $combined .= "=== VIDEO ANALYSIS ===\n" . implode("\n\n", $videoAnalysis) . "\n\n";
        }

        return $combined;
    }

    /**
     * Generate structured incident report using GPT-4
     */
    private function generateStructuredReport(array $evidence, string $combinedEvidence): array
    {
        $systemPrompt = <<<PROMPT
You are an Advanced Incident Analysis AI for law enforcement.

Your mission:
- Analyze ALL available evidence: text descriptions, image analysis, video transcripts and visual analysis
- Extract key information about people, actions, locations, timeline, evidence
- Generate a structured incident report following the EXACT format below

OUTPUT FORMAT (strict JSON):
{
    "incident_title": "Concise title (3-8 words) based on incident type and location",
    "summary": "2-3 sentences summarizing the incident (max 200 words)",
    "description": "Detailed chronological narrative synthesizing all evidence (max 500 words)",
    "people_involved": {
        "victims": [{"name": "Name/description", "details": "Age, injuries, condition"}],
        "suspects": [{"name": "Name/description", "details": "Appearance, actions, identifying features"}],
        "witnesses": [{"name": "Name/description", "details": "What they observed"}]
    },
    "actions_taken": {
        "emergency_response": ["Action 1", "Action 2"],
        "police_actions": ["Action 1", "Action 2"],
        "medical_interventions": ["Action 1", "Action 2"]
    },
    "severity_assessment": "low/medium/high/critical",
    "recommended_action": "Specific next steps for investigators"
}

CRITICAL RULES:
1. Extract information from ALL evidence sources (text, images, videos)
2. If audio transcript mentions people speaking, include their statements
3. If images show damage/injuries, describe them in detail
4. Use "Unknown" or "Not specified" only if genuinely no information available
5. Be factual and specific - include names, times, locations, descriptions from evidence
6. Severity: low=minor incident, medium=injuries/property damage, high=serious crimes, critical=deaths/terrorism
7. Return ONLY valid JSON, no markdown formatting
PROMPT;

        $userPrompt = <<<PROMPT
INCIDENT METADATA:
- Title: {$evidence['incident_title']}
- Date/Time: {$evidence['incident_datetime']}
- Location: {$evidence['incident_location']}

EVIDENCE:
{$combinedEvidence}

Generate the structured incident report in JSON format.
PROMPT;

        try {
            $response = $this->client->chat()->create([
                'model' => 'gpt-4-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userPrompt]
                ],
                'temperature' => 0.2,
                'max_tokens' => 2000,
                'response_format' => ['type' => 'json_object']
            ]);

            $reportText = $response->choices[0]->message->content;
            $structuredReport = json_decode($reportText, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Failed to parse OpenAI JSON response', ['response' => $reportText]);
                throw new \Exception('Invalid JSON response from OpenAI');
            }

            // Add metadata
            $structuredReport['metadata'] = [
                'analyzed_at' => now()->toIso8601String(),
                'model' => $this->visionModel,
                'evidence_sources' => [
                    'text_count' => count($evidence['text_evidence'] ?? []),
                    'image_count' => count($evidence['image_evidence'] ?? []),
                    'video_count' => count($evidence['video_evidence'] ?? []),
                ]
            ];

            return $structuredReport;

        } catch (\Exception $e) {
            Log::error('Failed to generate structured report', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Generate fallback report when AI analysis fails
     */
    private function generateFallbackReport(array $evidence): array
    {
        $textEvidence = implode("\n\n", $evidence['text_evidence'] ?? []);
        
        // Extract first 2-3 complete sentences for summary
        $sentences = preg_split('/(?<=[.!?])\s+/', $textEvidence);
        $summary = '';
        $charCount = 0;
        $sentenceCount = 0;

        foreach ($sentences as $sentence) {
            if ($sentenceCount >= 3 || $charCount + strlen($sentence) > 300) {
                break;
            }
            $summary .= $sentence . ' ';
            $charCount += strlen($sentence);
            $sentenceCount++;
        }

        // Fallback if no sentences found
        if (empty($summary)) {
            $summary = substr($textEvidence, 0, 300) . '...';
        }

        return [
            'incident_title' => $evidence['incident_title'] ?? 'Incident Report',
            'summary' => trim($summary),
            'description' => $textEvidence,
            'people_involved' => [
                'victims' => [['name' => 'Information not available - AI analysis failed. Please check the description for details.', 'details' => '']],
                'suspects' => [['name' => 'Not specified', 'details' => '']],
                'witnesses' => [['name' => 'Not specified', 'details' => '']]
            ],
            'actions_taken' => [
                'emergency_response' => ['Not specified - please check description for details'],
                'police_actions' => ['Not specified - please check description for details'],
                'medical_interventions' => ['Not specified - please check description for details']
            ],
            'severity_assessment' => 'medium',
            'recommended_action' => 'Review incident description and conduct manual assessment.',
            'metadata' => [
                'analyzed_at' => now()->toIso8601String(),
                'is_fallback' => true,
                'fallback_reason' => 'AI analysis unavailable or rate limited'
            ]
        ];
    }
}
