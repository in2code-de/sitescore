<?php

declare(strict_types=1);

namespace In2code\Sitescore\Domain\Repository;

use In2code\Sitescore\Exception\ApiException;
use In2code\Sitescore\Exception\ConfigurationException;
use In2code\Sitescore\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Http\RequestFactory;

class LlmRepository
{
    private string $apiKey = '';
    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/';
    private string $model = 'gemini-2.0-flash-exp:generateContent';

    public function __construct(
        private readonly RequestFactory $requestFactory,
    ) {
        $this->apiKey = getenv('GOOGLE_API_KEY') ?: ConfigurationUtility::getConfigurationByKey('apiKey') ?: '';
    }

    public function analyzePageContent(string $html, string $pageTitle): array
    {
        $this->checkApiKey();
        return $this->generateAnalysisWithGemini($html, $pageTitle);
    }

    protected function generateAnalysisWithGemini(string $html, string $pageTitle): array
    {
        $prompt = $this->buildAnalysisPrompt($html, $pageTitle);
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

        $additionalOptions = [
            'headers' => [
                'x-goog-api-key' => $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($payload),
        ];

        $response = $this->requestFactory->request($this->getApiUrl(), 'POST', $additionalOptions);

        if ($response->getStatusCode() !== 200) {
            throw new ApiException(
                'Failed to analyze page: ' . $response->getBody()->getContents(),
                1735042801
            );
        }

        $responseData = json_decode($response->getBody()->getContents(), true);
        return $this->parseGeminiResponse($responseData);
    }

    protected function buildAnalysisPrompt(string $html, string $pageTitle): string
    {
        return <<<PROMPT
Analyze the following HTML code of a webpage and provide a rating in the following categories (scale 0-100):

1. **GEO** (Geolocation/Local SEO): Meta tags for location, structured data for local businesses
2. **Performance**: Page structure, image optimization, CSS/JS inclusion
3. **Semantics**: Correct HTML5 semantics, heading hierarchy, ARIA labels
4. **Keyword Optimization**: Title tag, meta description, headings, content structure
5. **Online Marketing**: Social media tags (Open Graph, Twitter Cards), structured data

Also provide concrete improvement suggestions with priority (warning or success).

**Page Title**: {$pageTitle}

**HTML Code**:
```html
{$html}
```

**Response Format** (JSON only, no explanations):
```json
{
  "scores": {
    "geo": 85,
    "performance": 70,
    "semantics": 95,
    "keywords": 60,
    "marketing": 80
  },
  "suggestions": [
    {"type": "warning", "message": "2x H1 found on page"},
    {"type": "success", "message": "Meta description is optimal"},
    {"type": "warning", "message": "Alt text missing for 3 images"}
  ]
}
```
PROMPT;
    }

    protected function parseGeminiResponse(array $responseData): array
    {
        if (!isset($responseData['candidates'][0]['content']['parts'][0]['text'])) {
            throw new ApiException('Invalid Gemini API response structure', 1735042802);
        }

        $text = $responseData['candidates'][0]['content']['parts'][0]['text'];

        // Extract JSON from markdown code blocks if present
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $text, $matches)) {
            $text = $matches[1];
        } elseif (preg_match('/```\s*(\{.*?\})\s*```/s', $text, $matches)) {
            $text = $matches[1];
        }

        $data = json_decode($text, true);
        if (!$data || !isset($data['scores']) || !isset($data['suggestions'])) {
            throw new ApiException('Could not parse Gemini response as JSON', 1735042803);
        }

        return $data;
    }

    protected function getApiUrl(): string
    {
        return $this->apiUrl . $this->model;
    }

    protected function checkApiKey(): void
    {
        if ($this->apiKey === '') {
            throw new ConfigurationException('Google API key not configured', 1735042800);
        }
    }
}
