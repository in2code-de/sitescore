<?php

declare(strict_types=1);

namespace In2code\Sitescore\Domain\Service;

use In2code\Sitescore\Domain\Repository\AnalysisRepository;
use In2code\Sitescore\Domain\Repository\Llm\RepositoryInterface;
use In2code\Sitescore\Exception\CouldNotBuildAbsoluteUrlException;
use In2code\Sitescore\Exception\CurlException;
use In2code\Sitescore\Exception\UnexpectedValueException;
use In2code\Sitescore\Utility\UrlUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Site\SiteFinder;

final class AnalysisService
{
    public function __construct(
        readonly private RepositoryInterface $llmRepository,
        readonly private AnalysisRepository $analysisRepository,
        readonly private RequestFactory $requestFactory,
        readonly private SiteFinder $siteFinder,
    ) {}

    public function analyzePage(int $pageId, int $languageId = 0, ?ServerRequestInterface $request = null): array
    {
        if ($pageId <= 0) {
            throw new UnexpectedValueException('No valid page identifier provided', 1766489924);
        }

        $html = $this->fetchPageHtml($pageId, $languageId, $request);
        $pageTitle = $this->extractPageTitle($html);
        $analysis = $this->llmRepository->analyzePageContent($html, $pageTitle);

        $scores = $analysis['scores'] ?? [];
        $suggestions = $analysis['suggestions'] ?? [];

        $this->analysisRepository->save($pageId, $scores, $suggestions, $languageId);

        return [
            'scores' => $scores,
            'suggestions' => $suggestions,
            'pageTitle' => $pageTitle,
            'languageId' => $languageId,
        ];
    }

    private function fetchPageHtml(int $pageId, int $languageId, ?ServerRequestInterface $request): string
    {
        $url = $this->getPageUrl($pageId, $languageId, $request);
        $response = $this->requestFactory->request($url);
        if ($response->getStatusCode() !== 200) {
            throw new CurlException('Could not fetch page HTML: HTTP ' . $response->getStatusCode(), 1735042810);
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

    private function getPageUrl(int $pageId, int $languageId, ?ServerRequestInterface $request): string
    {
        $site = $this->siteFinder->getSiteByPageId($pageId);
        $language = $site->getLanguageById($languageId);
        $uri = $site->getRouter()->generateUri($pageId, ['_language' => $language]);
        $url = $uri->__toString();
        if ($request !== null) {
            $url = UrlUtility::makeAbsoluteWithCurrentDomain($url, $request);
        }
        if (UrlUtility::isAbsoluteUrl($url) === false) {
            throw new CouldNotBuildAbsoluteUrlException(
                'Could not build absolute URL for page ID ' . $pageId .
                '. (Base must not be "/" in Site configuration for CLI command)',
                1766737582
            );
        }
        return $url;
    }
}
