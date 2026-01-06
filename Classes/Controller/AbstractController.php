<?php

declare(strict_types=1);

namespace In2code\Sitescore\Controller;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

abstract class AbstractController
{
    protected int $pageIdentifier = 0;

    public function __construct(
        protected readonly ConnectionPool $connectionPool,
    ) {}

    protected function setPageIdentifier(ServerRequestInterface $request): void
    {
        $this->pageIdentifier = (int)($request->getParsedBody()['pageId'] ?? $request->getQueryParams()['pageId'] ?? 0);
    }

    protected function getLanguageId(ServerRequestInterface $request): int
    {
        $languageId = (int)($request->getQueryParams()['language'] ?? 0);
        if ($languageId <= 0) {
            $languageId = 0;
        }
        return $languageId;
    }

    protected function getAvailableLanguageId(int $pageId, int $languageId): int
    {
        if ($languageId > 0) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
            $count = $queryBuilder
                ->count('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)),
                    $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT))
                )
                ->executeQuery()
                ->fetchOne();
            $languageId = ($count > 0 ? $languageId : 0);
        }
        return $languageId;
    }
}