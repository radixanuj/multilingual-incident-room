<?php

namespace App\Services;

use LingoDotDev\Sdk\LingoDotDevEngine;
use Illuminate\Support\Facades\Log;

class LingoSdkService
{
    private LingoDotDevEngine $engine;

    public function __construct()
    {
        $this->engine = new LingoDotDevEngine([
            'apiKey' => config('services.lingo.api_key') ?? env('LINGO_API_KEY'),
            'timeout' => 120, // 2 minutes timeout
            'connectTimeout' => 30, // 30 seconds connection timeout
        ]);
    }

    /**
     * Translate text from source language to target language
     *
     * @param string $text The text to translate
     * @param string $source Source language code ('hi', 'bn', 'en', 'auto')
     * @param string $target Target language code ('hi', 'bn', 'en')
     * @return string Translated text
     */
    public function translate(string $text, string $source = 'auto', string $target = 'en'): string
    {
        try {
            // Clean UTF-8 before translation
            $text = $this->cleanUtf8($text);
            return $this->performTranslation($text, $source, $target);
        } catch (\Exception $e) {
            // Fallback: return original text if translation fails
            Log::warning('Lingo translation failed: ' . $e->getMessage());
            return $text;
        }
    }

    /**
     * Generate localized versions of English text
     *
     * @param string $sourceText English source text
     * @param array $targetLocales Array of target locale codes
     * @return array Associative array with locale codes as keys
     */
    public function compile(string $sourceText, array $targetLocales = ['hi', 'bn']): array
    {
        try {
            // Clean UTF-8 before translation
            $sourceText = $this->cleanUtf8($sourceText);
            return $this->performBatchTranslation($sourceText, $targetLocales);
        } catch (\Exception $e) {
            Log::warning('Lingo batch translation failed, falling back to individual translations: ' . $e->getMessage());
            return $this->fallbackIndividualTranslations($sourceText, $targetLocales);
        }
    }

    /**
     * Detect language of given text
     *
     * @param string $text Text to detect language for
     * @return string Detected language code
     */
    public function detectLanguage(string $text): string
    {
        try {
            // Clean UTF-8 before language detection
            $text = $this->cleanUtf8($text);
            return $this->engine->recognizeLocale($text);
        } catch (\Exception $e) {
            Log::warning('Lingo language detection failed: ' . $e->getMessage());
            return $this->fallbackLanguageDetection($text);
        }
    }
    
    /**
     * Clean malformed UTF-8 characters from text
     */
    private function cleanUtf8(string $text): string
    {
        // Remove invalid UTF-8 characters
        return mb_convert_encoding($text, 'UTF-8', 'UTF-8');
    }

    /**
     * Perform translation using Lingo SDK
     */
    private function performTranslation(string $text, string $source, string $target): string
    {
        // If source is auto, detect the language first
        if ($source === 'auto') {
            $source = $this->engine->recognizeLocale($text);
        }

        // If source and target are the same, return original
        if ($source === $target) {
            return $text;
        }

        // Use Lingo SDK to translate
        return $this->engine->localizeText($text, [
            'sourceLocale' => $source,
            'targetLocale' => $target,
        ]);
    }

    /**
     * Perform batch translation using Lingo SDK
     */
    private function performBatchTranslation(string $sourceText, array $targetLocales): array
    {
        // Use batch localization for efficiency
        $translations = $this->engine->batchLocalizeText($sourceText, [
            'sourceLocale' => 'en',
            'targetLocales' => $targetLocales,
        ]);

        // Map the translations to locale codes
        $result = [];
        foreach ($targetLocales as $index => $locale) {
            $result[$locale] = $translations[$index] ?? $sourceText;
        }

        return $result;
    }

    /**
     * Fallback to individual translations when batch fails
     */
    private function fallbackIndividualTranslations(string $sourceText, array $targetLocales): array
    {
        $result = [];
        foreach ($targetLocales as $locale) {
            $result[$locale] = $this->translate($sourceText, 'en', $locale);
        }
        return $result;
    }

    /**
     * Simple fallback language detection based on script
     */
    private function fallbackLanguageDetection(string $text): string
    {
        if (preg_match('/[\x{0900}-\x{097F}]/u', $text)) {
            return 'hi'; // Devanagari script (Hindi)
        }

        if (preg_match('/[\x{0980}-\x{09FF}]/u', $text)) {
            return 'bn'; // Bengali script
        }

        return 'en'; // Default to English
    }
}
