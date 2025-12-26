<?php

declare(strict_types=1);

namespace In2code\SitescoreTest\Domain\Repository\Llm;

use In2code\Sitescore\Domain\Repository\Llm\RepositoryInterface;
use In2code\Sitescore\Exception\ApiException;
use In2code\Sitescore\Exception\ConfigurationException;
use TYPO3\CMS\Core\Http\RequestFactory;

class MistralRepository implements RepositoryInterface
{
    private string $apiKey = '';
    private string $apiUrl = 'https://api.mistral.ai/v1/chat/completions';

    public function __construct(
        protected RequestFactory $requestFactory,
    ) {
        // Get API key from environment variable or extension configuration
        $this->apiKey = getenv('MISTRAL_API_KEY') ?: '';
    }

    public function checkApiKey(): void
    {
        if ($this->apiKey === '') {
            throw new ConfigurationException('Mistral API key not configured', 1735200000);
        }
    }

    public function getApiUrl(): string
    {
        return $this->apiUrl;
    }

    public function getOptions(string $html, string $pageTitle): array
    {
        $prompt = $this->getPrompt($html, $pageTitle);

        return [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->apiKey,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode([
                'model' => 'mistral-large-latest',
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt,
                    ],
                ],
                'temperature' => 0.1,
                'max_tokens' => 2048,
            ]),
        ];
    }

    public function analyzePageContent(string $html, string $pageTitle): array
    {
        $this->checkApiKey();
        return $this->generateAnalysis($html, $pageTitle);
    }

    protected function generateAnalysis(string $html, string $pageTitle): array
    {
        $response = $this->requestFactory->request(
            $this->getApiUrl(),
            'POST',
            $this->getOptions($html, $pageTitle)
        );

        if ($response->getStatusCode() !== 200) {
            throw new ApiException(
                'Failed to analyze page with Mistral: ' . $response->getBody()->getContents(),
                1735200001
            );
        }

        $responseData = json_decode($response->getBody()->getContents(), true);
        return $this->parseResponse($responseData);
    }

    protected function parseResponse(array $responseData): array
    {
        if (isset($responseData['choices'][0]['message']['content']) === false) {
            throw new ApiException('Invalid Mistral API response structure', 1735200002);
        }

        $text = $responseData['choices'][0]['message']['content'];

        // Extract JSON from markdown code blocks if present
        if (preg_match('/```json\s*(\{.*?\})\s*```/s', $text, $matches)) {
            $text = $matches[1];
        } elseif (preg_match('/```\s*(\{.*?\})\s*```/s', $text, $matches)) {
            $text = $matches[1];
        }

        $data = json_decode($text, true);
        if ($data === false || isset($data['scores']) === false || isset($data['suggestions']) === false) {
            throw new ApiException('Could not parse Mistral response as JSON', 1735200003);
        }

        return $data;
    }

    protected function getPrompt(string $html, string $pageTitle): string
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

IMPORTANT: Respond ONLY with valid JSON in the exact format shown above. Do not include any additional text or explanations.
PROMPT;
    }
}
