<?php

declare(strict_types=1);

namespace In2code\Sitescore\Controller;

use In2code\Sitescore\Domain\Repository\AnalysisRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\JsonResponse;

/**
 * Class LoadAnalysisController
 * to load an existing analysis from a cache table
 */
class LoadAnalysisController extends AbstractController
{
    public function __construct(
        private readonly AnalysisRepository $analysisRepository,
        ConnectionPool $connectionPool,
    ) {
        parent::__construct($connectionPool);
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $this->setPageIdentifier($request);
        if ($this->pageIdentifier <= 0) {
            return new JsonResponse(['success' => false, 'error' => 'Invalid page ID'], 400);
        }
        $languageId = $this->getAvailableLanguageId($this->pageIdentifier, $this->getLanguageId($request));
        $analysis = $this->analysisRepository->findByPageIdentifier($this->pageIdentifier, $languageId);
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
}
