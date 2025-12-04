<?php

namespace App\Service;

use Psr\Log\LoggerInterface;

class WordParserService
{
    public function __construct(
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Parse AI response and extract words
     *
     * @param string $jsonResponse JSON response from AI
     * @return array Array of word data [word, translation, example]
     * @throws \Exception If parsing fails
     */
    public function parseAIResponse(string $jsonResponse): array
    {
        // Clean up response - sometimes AI wraps JSON in markdown code blocks
        $jsonResponse = $this->cleanJsonResponse($jsonResponse);

        try {
            $data = json_decode($jsonResponse, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            $this->logger->error('Failed to parse JSON response', [
                'error' => $e->getMessage(),
                'response' => substr($jsonResponse, 0, 500),
            ]);
            throw new \Exception('Invalid JSON response from AI: ' . $e->getMessage(), 0, $e);
        }

        // Handle different response structures
        $words = [];

        // Check if response has 'vocabulary' key (like in our test)
        if (isset($data['vocabulary']) && is_array($data['vocabulary'])) {
            $words = $data['vocabulary'];
        }
        // Check if response is directly an array
        elseif (is_array($data) && isset($data[0])) {
            $words = $data;
        }
        // Check if response has 'words' key
        elseif (isset($data['words']) && is_array($data['words'])) {
            $words = $data['words'];
        } else {
            $this->logger->error('Unexpected JSON structure', ['data' => $data]);
            throw new \Exception('Unexpected JSON structure in AI response');
        }

        $parsedWords = [];

        foreach ($words as $index => $wordData) {
            try {
                $parsed = $this->parseWord($wordData, $index);
                $parsedWords[] = $parsed;
            } catch (\Exception $e) {
                $this->logger->warning('Failed to parse word', [
                    'index' => $index,
                    'data' => $wordData,
                    'error' => $e->getMessage(),
                ]);
                // Skip invalid words instead of failing completely
                continue;
            }
        }

        if (empty($parsedWords)) {
            throw new \Exception('No valid words found in AI response');
        }

        $this->logger->info('Successfully parsed words', [
            'count' => count($parsedWords),
        ]);

        return $parsedWords;
    }

    private function cleanJsonResponse(string $response): string
    {
        // Remove markdown code blocks if present
        $response = preg_replace('/^```json\s*/m', '', $response);
        $response = preg_replace('/^```\s*/m', '', $response);
        $response = trim($response);

        return $response;
    }

    private function parseWord(array $wordData, int $index): array
    {
        // Validate required fields
        if (!isset($wordData['word']) || empty($wordData['word'])) {
            throw new \Exception("Missing 'word' field at index {$index}");
        }

        if (!isset($wordData['translation']) || empty($wordData['translation'])) {
            throw new \Exception("Missing 'translation' field at index {$index}");
        }

        if (!isset($wordData['example']) || empty($wordData['example'])) {
            throw new \Exception("Missing 'example' field at index {$index}");
        }

        // Sanitize and prepare data
        return [
            'word' => $this->sanitize($wordData['word']),
            'translation' => $this->sanitize($wordData['translation']),
            'example' => $this->sanitize($wordData['example']),
        ];
    }

    private function sanitize(string $value): string
    {
        // Trim whitespace
        $value = trim($value);

        // Remove any null bytes
        $value = str_replace("\0", '', $value);

        // Normalize whitespace
        $value = preg_replace('/\s+/', ' ', $value);

        return $value;
    }
}
