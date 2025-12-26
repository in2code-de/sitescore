<?php

declare(strict_types=1);

namespace In2code\Sitescore\Domain\Repository;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Class AnalysisRepository
 * to store data from AI to local database for caching
 */
class AnalysisRepository
{
    private const TABLE_NAME = 'tx_sitescore_analysis';

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function findByPageIdentifier(int $pageIdentifier): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $row = $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageIdentifier, Connection::PARAM_INT))
            )
            ->orderBy('crdate', 'DESC')
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if ($row === false) {
            return null;
        }

        return [
            'scores' => json_decode($row['scores'] ?? '{}', true),
            'suggestions' => json_decode($row['suggestions'] ?? '[]', true),
            'analyzed_at' => (int)($row['crdate'] ?? 0),
        ];
    }

    public function save(int $pageIdentifier, array $scores, array $suggestions): void
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_NAME);
        $connection->delete(self::TABLE_NAME, ['pid' => $pageIdentifier]);
        $connection->insert(
            self::TABLE_NAME,
            [
                'pid' => $pageIdentifier,
                'scores' => json_encode($scores),
                'suggestions' => json_encode($suggestions),
                'crdate' => time(),
            ]
        );
    }
}
