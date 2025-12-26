<?php

declare(strict_types=1);

namespace In2code\Sitescore\Controller;

use In2code\Sitescore\Domain\Service\AnalysisService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

/**
 * Class AnalysisController
 * to do a fresh new analysis
 */
class AnalysisController extends AbstractController
{
    public function __construct(
        readonly private AnalysisService $analysisService,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $this->setPageIdentifier($request);

        try {
            $result = $this->analysisService->analyzePage(
                $this->pageIdentifier,
                (int)($request->getQueryParams()['language'] ?? 0),
                $request
            );

            return new JsonResponse([
                'success' => true,
                'scores' => $result['scores'],
                'suggestions' => $result['suggestions'],
                'languageId' => $result['languageId'],
            ]);
        } catch (\Throwable $exception) {
            return new JsonResponse([
                'success' => false,
                'error' => $exception->getMessage() . ' (' . $exception->getCode() . ')',
            ], 500);
        }
    }
}
