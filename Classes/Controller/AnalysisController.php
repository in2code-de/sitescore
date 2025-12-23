<?php

declare(strict_types=1);

namespace In2code\Sitescore\Controller;

use In2code\Sitescore\Domain\Repository\AnalysisRepository;
use In2code\Sitescore\Domain\Repository\LlmRepository;
use In2code\Sitescore\Exception\UnexpectedValueException;
use In2code\Sitescore\Utility\UrlUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Site\SiteFinder;

class AnalysisController extends AbstractController
{

    public function __construct(
        private readonly LlmRepository $llmRepository,
        private readonly AnalysisRepository $analysisRepository,
        private readonly RequestFactory $requestFactory,
        private readonly SiteFinder $siteFinder,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $this->setPageIdentifier($request);

        try {
            if ($this->pageIdentifier <= 0) {
                throw new UnexpectedValueException('No valid page identifier provided', 1766489924);
            }
            $html = $this->fetchPageHtml($request);
            $pageTitle = $this->extractPageTitle($html);
            $analysis = $this->llmRepository->analyzePageContent($html, $pageTitle);

            // Persist results
            $this->analysisRepository->save(
                $this->pageIdentifier,
                $analysis['scores'] ?? [],
                $analysis['suggestions'] ?? []
            );

            return new JsonResponse([
                'success' => true,
                'scores' => $analysis['scores'] ?? [],
                'suggestions' => $analysis['suggestions'] ?? [],
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'error' => $exception->getMessage() . ' (' . $exception->getCode() . ')',
            ], 500);
        }
    }

    private function fetchPageHtml(ServerRequestInterface $request): string
    {
        $response = $this->requestFactory->request($this->getPageUrl($request));

        if ($response->getStatusCode() !== 200) {
            throw new \RuntimeException('Could not fetch page HTML: HTTP ' . $response->getStatusCode(), 1735042810);
        }

        return $response->getBody()->getContents();
    }

    private function extractPageTitle(string $html): string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            return html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return 'Unknown';
    }

    protected function getPageUrl(ServerRequestInterface $request): string
    {
        $site = $this->siteFinder->getSiteByPageId($this->pageIdentifier);
        $uri = $site->getRouter()->generateUri($this->pageIdentifier);
        $url = $uri->__toString();
        return UrlUtility::makeAbsoluteWithCurrentDomain($url, $request);
    }
}
