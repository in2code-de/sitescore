<?php

declare(strict_types=1);

namespace In2code\SitescoreTest\Domain\Repository\Llm;

use In2code\Sitescore\Domain\Repository\Llm\AbstractRepository;
use In2code\Sitescore\Domain\Repository\Llm\RepositoryInterface;
use In2code\Sitescore\Exception\ApiException;
use In2code\Sitescore\Exception\ConfigurationException;
use TYPO3\CMS\Core\Http\RequestFactory;

class MistralRepository extends AbstractRepository implements RepositoryInterface
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

    public function getOptions(string $html, string $pageTitle, string $keyword): array
    {
        $prompt = $this->getPrompt($html, $pageTitle, $keyword);

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

    public function analyzePageContent(string $html, string $pageTitle, string $keyword): array
    {
        $this->checkApiKey();
        return $this->generateAnalysis($html, $pageTitle, $keyword);
    }

    protected function generateAnalysis(string $html, string $pageTitle, string $keyword): array
    {
        $response = $this->requestFactory->request(
            $this->getApiUrl(),
            'POST',
            $this->getOptions($html, $pageTitle, $keyword)
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
}
