<?php

declare(strict_types=1);

namespace In2code\Sitescore\Controller;

use In2code\Sitescore\Utility\BackendUserUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;

class ToggleController
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $collapsedRaw = $request->getParsedBody()['collapsed'] ?? false;
        if ($collapsedRaw === 'false' || $collapsedRaw === false || $collapsedRaw === 0 || $collapsedRaw === '0') {
            $collapsed = false;
        } else {
            $collapsed = (bool)$collapsedRaw;
        }
        BackendUserUtility::setCollapsedState($collapsed);
        return new JsonResponse([
            'success' => true,
            'collapsed' => $collapsed,
        ]);
    }
}
