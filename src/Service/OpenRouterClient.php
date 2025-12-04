<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Psr\Log\LoggerInterface;

class OpenRouterClient
{
    private const API_URL = 'https://openrouter.ai/api/v1/chat/completions';
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY = 2; // seconds

    public function __construct(
        private HttpClientInterface $httpClient,
        #[Autowire(env: 'OPENROUTER_API_KEY')]
        private string $apiKey,
        #[Autowire(env: 'OPENROUTER_MODEL')]
        private string $model,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Generate words using OpenRouter API
     *
     * @param string $prompt The prompt to send to the AI
     * @param float $temperature The temperature for generation (0.0 - 1.0)
     * @param int $maxTokens Maximum tokens to generate
     * @return string The AI response content
     * @throws \Exception
     */
    public function generate(string $prompt, float $temperature = 0.8, int $maxTokens = 3000): string
    {
        $attempt = 0;
        $lastException = null;

        while ($attempt < self::MAX_RETRIES) {
            try {
                $attempt++;

                $this->logger->info('OpenRouter API call', [
                    'attempt' => $attempt,
                    'model' => $this->model,
                    'prompt_length' => strlen($prompt),
                ]);

                $response = $this->httpClient->request('POST', self::API_URL, [
                    'headers' => [
                        'Authorization' => 'Bearer ' . $this->apiKey,
                        'Content-Type' => 'application/json',
                        'HTTP-Referer' => 'https://github.com/KamilNisiewicz/kamingo',
                        'X-Title' => 'Kamingo - Language Learning App',
                    ],
                    'json' => [
                        'model' => $this->model,
                        'messages' => [
                            [
                                'role' => 'user',
                                'content' => $prompt,
                            ],
                        ],
                        'temperature' => $temperature,
                        'max_tokens' => $maxTokens,
                    ],
                    'timeout' => 60,
                ]);

                $statusCode = $response->getStatusCode();

                if ($statusCode === 429) {
                    // Rate limit exceeded
                    if ($attempt < self::MAX_RETRIES) {
                        $this->logger->warning('Rate limit exceeded, retrying...', ['attempt' => $attempt]);
                        sleep(self::RETRY_DELAY * $attempt); // Exponential backoff
                        continue;
                    }
                    throw new \Exception('OpenRouter API rate limit exceeded. Please try again later.');
                }

                if ($statusCode !== 200) {
                    $content = $response->getContent(false);
                    $this->logger->error('OpenRouter API error', [
                        'status_code' => $statusCode,
                        'response' => $content,
                    ]);
                    throw new \Exception(sprintf('OpenRouter API returned status code %d: %s', $statusCode, $content));
                }

                $data = $response->toArray();

                if (!isset($data['choices'][0]['message']['content'])) {
                    $this->logger->error('Invalid OpenRouter API response structure', ['response' => $data]);
                    throw new \Exception('Invalid response from OpenRouter API: missing content');
                }

                $content = $data['choices'][0]['message']['content'];

                if (empty($content)) {
                    $this->logger->error('Empty response from OpenRouter API');
                    throw new \Exception('OpenRouter API returned empty response');
                }

                $this->logger->info('OpenRouter API call successful', [
                    'response_length' => strlen($content),
                    'attempt' => $attempt,
                ]);

                return $content;

            } catch (TransportExceptionInterface $e) {
                $lastException = $e;
                $this->logger->error('Transport exception in OpenRouter API call', [
                    'message' => $e->getMessage(),
                    'attempt' => $attempt,
                ]);

                if ($attempt < self::MAX_RETRIES) {
                    sleep(self::RETRY_DELAY);
                    continue;
                }

                throw new \Exception('Failed to connect to OpenRouter API: ' . $e->getMessage(), 0, $e);
            } catch (\Exception $e) {
                // Re-throw non-transport exceptions immediately
                throw $e;
            }
        }

        // If we exhausted all retries
        throw new \Exception(
            'Failed to call OpenRouter API after ' . self::MAX_RETRIES . ' attempts',
            0,
            $lastException
        );
    }
}
