<?php

declare(strict_types=1);

namespace In2code\Sitescore\Domain\Repository\Llm;

use In2code\Sitescore\Exception\ApiException;
use TYPO3\CMS\Core\Http\RequestFactory;

abstract class AbstractRepository
{
    protected string $requestMethod = 'POST';

    public function __construct(
        protected RequestFactory $requestFactory,
    ) {}

    public function analyzePageContent(string $html, string $pageTitle): array
    {
        $this->checkApiKey();
        return $this->generateAnalysis($html, $pageTitle);
    }

    protected function generateAnalysis(string $html, string $pageTitle): array
    {
        $response = $this->requestFactory->request(
            $this->getApiUrl(),
            $this->requestMethod,
            $this->getOptions($html, $pageTitle)
        );
        if ($response->getStatusCode() !== 200) {
            throw new ApiException(
                'Failed to analyze page: ' . $response->getBody()->getContents(),
                1735042801
            );
        }
        $responseData = json_decode($response->getBody()->getContents(), true);
        return $this->parseResponse($responseData);
    }

    protected function parseResponse(array $responseData): array
    {
        return $responseData;
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
PROMPT;
    }
}
