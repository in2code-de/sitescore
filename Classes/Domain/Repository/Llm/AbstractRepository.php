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
**IMPORTANT**: You are a content quality and SEO expert. Focus on QUALITATIVE assessment, NOT technical element counting.
Technical checks (H1 count, alt attributes, meta tags) are handled separately - do NOT duplicate these in your suggestions.

Analyze the following HTML code and provide a rating in these categories (scale 0-100):

1. **GEO** (Generative Engine Optimization/Search Engine Optimization):
   - Structured data presence and quality (schema.org, JSON-LD, Microdata, RDFa)
   - Semantic markup for search engines
   - Content readability for AI/crawlers

2. **Performance**:
   - Image file sizes and formats (webp, lazy loading)
   - CSS/JS optimization (inline critical CSS, deferred scripts)
   - Page structure efficiency

3. **Semantics**:
   - Proper HTML5 semantic elements (article, section, nav, aside, etc.)
   - Meaningful heading hierarchy (content structure, not just H1 count)
   - ARIA labels and roles where appropriate

4. **Keyword Optimization**:
   - Keyword density and natural placement
   - Keyword in strategic locations (first paragraph, headings)
   - Related terms and semantic relevance
   - Content relevance to target keyword

5. **Accessibility**:
   - WCAG compliance beyond alt texts
   - Keyboard navigation support
   - Screen reader compatibility
   - Color contrast ratios
   - Form label associations

**Focus your suggestions on**:
- Content quality and structure
- SEO best practices
- Schema.org recommendations
- Accessibility improvements beyond basic checks
- Performance optimization opportunities
- Keyword integration quality

**DO NOT suggest**:
- Basic element counts (H1, images, etc.)
- Missing alt attributes
- Missing meta descriptions
- Missing title tags
These are checked automatically.

**Page Title**: {$pageTitle}

{$keywordInfo}

**HTML Code**:
```html
{$html}
```

**Response Format** (JSON only, no explanations outside JSON):
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
    {"type": "success", "message": "Good use of schema.org Article markup"},
    {"type": "warning", "message": "Keyword density too low - appears only 2 times in 800 words"},
    {"type": "info", "message": "Consider adding FAQ schema for better SERP features"},
    {"type": "warning", "message": "Images could be optimized - use WebP format for better performance"}
  ]
}
```
PROMPT;
    }
}
