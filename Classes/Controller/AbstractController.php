<?php

declare(strict_types=1);

namespace In2code\Sitescore\Controller;

use Psr\Http\Message\ServerRequestInterface;

abstract class AbstractController
{
    protected int $pageIdentifier = 0;

    protected function setPageIdentifier(ServerRequestInterface $request): void
    {
        $this->pageIdentifier = (int)($request->getParsedBody()['pageId'] ?? $request->getQueryParams()['pageId'] ?? 0);
    }
}