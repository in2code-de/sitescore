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

    public function analyzePageContent(string $html, string $pageTitle, string $keyword): array
    {
        $this->checkApiKey();
        return $this->generateAnalysis($html, $pageTitle, $keyword);
    }

    protected function generateAnalysis(string $html, string $pageTitle, string $keyword): array
    {
        $response = $this->requestFactory->request(
            $this->getApiUrl(),
            $this->requestMethod,
            $this->getOptions($html, $pageTitle, $keyword)
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

    protected function getPrompt(string $html, string $pageTitle, string $keyword): string
    {
        $keywordInfo = '**Target Keyword**: ' . $keyword;
        $keywordInfo .= PHP_EOL . PHP_EOL;
        $keywordInfo .= 'Please analyze how well this keyword is optimized throughout the page (in title, headings, meta description, and content).';
        if ($keyword === '') {
            $keywordInfo = '**Target Keyword**: Not specified\n\n**IMPORTANT**: Set the \'keywords\' score to 0 because no target keyword was provided for this page. This should then be the first suggestion to add a keyword in TYPO3 backend.';
        }

        return <<<PROMPT
Analyze the following HTML code of a webpage and provide a rating in the following categories (scale 0-100):

1. **GEO** (Generative Engine Optimization/Search Engine Optimization): Structured and readable content for machines with schema.org tools (e.gl JSON-LD, Microdata, RDFa)
2. **Performance**: Page structure, image optimization, CSS/JS inclusion
3. **Semantics**: Correct HTML5 semantics, heading hierarchy, ARIA labels
4. **Keyword Optimization**: Title tag, meta description, headings, content structure (check optimization for the target keyword)
5. **Accessibility**: WCAG compliance, keyboard navigation, screen reader support, color contrast, alt texts, form labels

Also provide concrete improvement suggestions with priority (warning or success).

**Page Title**: {$pageTitle}

{$keywordInfo}

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
    "accessibility": 90
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
