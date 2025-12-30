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
        BackendUserUtility::setCollapsedState($this->isCollapsed($request));
        return new JsonResponse([
            'success' => true,
            'collapsed' => $this->isCollapsed($request),
        ]);
    }

    protected function isCollapsed(ServerRequestInterface $request): bool
    {
        $collapsedRaw = $request->getParsedBody()['collapsed'] ?? false;
        if ($collapsedRaw === 'false' || $collapsedRaw === false || $collapsedRaw === 0 || $collapsedRaw === '0') {
            return false;
        }
        return true;
    }
}
