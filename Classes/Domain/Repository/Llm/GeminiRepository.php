<?php

declare(strict_types=1);

namespace In2code\Sitescore\Domain\Repository\Llm;

use In2code\Sitescore\Exception\ApiException;
use In2code\Sitescore\Exception\ConfigurationException;
use In2code\Sitescore\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Http\RequestFactory;

class GeminiRepository extends AbstractRepository implements RepositoryInterface
{
    private string $apiKey = '';
    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private string $model = 'gemini-2.0-flash-exp:generateContent';

    public function __construct(
        protected RequestFactory $requestFactory,
    ) {
        parent::__construct($requestFactory);
        $this->apiKey = getenv('GOOGLE_API_KEY') ?: ConfigurationUtility::getConfigurationByKey('apiKey') ?: '';
    }

    public function checkApiKey(): void
    {
        if ($this->apiKey === '') {
            throw new ConfigurationException('Google API key not configured', 1735042800);
        }
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl . $this->model;
    }

    public function getOptions(string $html, string $pageTitle): array
    {
        $prompt = $this->getPrompt($html, $pageTitle);
        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'topK' => 1,
                'topP' => 1,
                'maxOutputTokens' => 2048,
            ],
        ];

        return [
            'headers' => [
                'x-goog-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($payload),
        ];
    }

    protected function parseResponse(array $responseData): array
    {
        if (isset($responseData['candidates'][0]['content']['parts'][0]['text']) === false) {
            throw new ApiException('Invalid Gemini API response structure', 1766737583);
        }

        $text = $responseData['candidates'][0]['content']['parts'][0]['text'];

        // Extract JSON from markdown code blocks if present
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $text, $matches)) {
            $text = $matches[1];
        } elseif (preg_match('/```\s*(\{.*?\})\s*```/s', $text, $matches)) {
            $text = $matches[1];
        }

        $data = json_decode($text, true);
        if ($data === false || isset($data['scores']) === false || isset($data['suggestions']) === false) {
            throw new ApiException('Could not parse Gemini response as JSON', 1766737581);
        }

        return $data;
    }
}
