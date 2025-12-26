<?php

declare(strict_types=1);

namespace In2code\Sitescore\Controller;

use In2code\Sitescore\Domain\Repository\AnalysisRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

/**
 * Class LoadAnalysisController
 * to load an existing analysis from cache table
 */
class LoadAnalysisController extends AbstractController
{
    public function __construct(
        private readonly AnalysisRepository $analysisRepository,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $this->setPageIdentifier($request);
        if ($this->pageIdentifier <= 0) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid page ID'], 400);
        }
        $analysis = $this->analysisRepository->findByPageIdentifier($this->pageIdentifier, $this->getLanguageId($request));
        if ($analysis === null) {
            return new JsonResponse(['success' => false, 'hasData' => false]);
        }
        return new JsonResponse([
            'success' => true,
            'hasData' => true,
            'scores' => $analysis['scores'] ?? [],
            'suggestions' => $analysis['suggestions'] ?? [],
            'analyzed_at' => $analysis['analyzed_at'] ?? 0,
        ]);
    }

    private function getLanguageId(ServerRequestInterface $request): int
    {
        $languageId = (int)($request->getQueryParams()['language'] ?? 0);
        if ($languageId <= 0) {
            $languageId = 0;
        }
        return $languageId;
    }
}
