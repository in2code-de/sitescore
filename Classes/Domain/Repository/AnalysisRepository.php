<?php

declare(strict_types=1);

namespace In2code\Sitescore\Domain\Repository;

use In2code\Sitescore\Domain\Model\ScoreRange;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Class AnalysisRepository
 * to store data from AI to a local database for caching
 */
class AnalysisRepository
{
    private const TABLE_NAME = 'tx_sitescore_analysis';

    private const EMPTY_SCORES = [
        'geo' => 0,
        'performance' => 0,
        'semantics' => 0,
        'keywords' => 0,
        'accessibility' => 0,
    ];

    public function __construct(
        private readonly ConnectionPool $connectionPool,
    ) {}

    public function findByPageIdentifier(int $pageIdentifier, int $languageId = 0): ?array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $row = $queryBuilder
            ->select('*')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageIdentifier, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT))
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

    public function save(int $pageIdentifier, array $scores, array $suggestions, int $languageId = 0): void
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_NAME);
        $connection->delete(
            self::TABLE_NAME,
            [
                'pid' => $pageIdentifier,
                'sys_language_uid' => $languageId,
            ]
        );
        $connection->insert(
            self::TABLE_NAME,
            [
                'pid' => $pageIdentifier,
                'sys_language_uid' => $languageId,
                'scores' => json_encode($scores),
                'suggestions' => json_encode($suggestions),
                'crdate' => time(),
            ]
        );
    }

    public function truncate(): void
    {
        $connection = $this->connectionPool->getConnectionForTable(self::TABLE_NAME);
        $connection->truncate(self::TABLE_NAME);
    }

    public function countAllAnalyzedPages(int $languageId = 0): int
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $count = $queryBuilder
            ->selectLiteral('COUNT(DISTINCT pid)')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne();

        return (int)$count;
    }

    public function getAverageScores(int $languageId = 0): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $rows = $queryBuilder
            ->select('scores')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        if (empty($rows)) {
            return self::EMPTY_SCORES;
        }

        $sums = self::EMPTY_SCORES;
        $validCount = 0;

        foreach ($rows as $row) {
            $scores = json_decode($row['scores'] ?? '{}', true);
            if (!is_array($scores)) {
                continue;
            }

            foreach ($sums as $category => $value) {
                if (isset($scores[$category]) && is_numeric($scores[$category])) {
                    $sums[$category] += (int)$scores[$category];
                }
            }
            $validCount++;
        }

        if ($validCount === 0) {
            return self::EMPTY_SCORES;
        }

        return [
            'geo' => (int)round($sums['geo'] / $validCount),
            'performance' => (int)round($sums['performance'] / $validCount),
            'semantics' => (int)round($sums['semantics'] / $validCount),
            'keywords' => (int)round($sums['keywords'] / $validCount),
            'accessibility' => (int)round($sums['accessibility'] / $validCount),
        ];
    }

    /**
     * Get score distribution for a specific category
     * Returns array with counts: ['high' => X, 'medium' => Y, 'low' => Z]
     * Uses ScoreRange enum for threshold definitions
     */
    public function getScoreDistribution(string $category, int $languageId = 0): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable(self::TABLE_NAME);
        $rows = $queryBuilder
            ->select('scores')
            ->from(self::TABLE_NAME)
            ->where(
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchAllAssociative();

        $distribution = [
            ScoreRange::HIGH->value => 0,
            ScoreRange::MEDIUM->value => 0,
            ScoreRange::LOW->value => 0,
        ];

        foreach ($rows as $row) {
            $scores = json_decode($row['scores'] ?? '{}', true);
            if (!is_array($scores) || !isset($scores[$category]) || !is_numeric($scores[$category])) {
                continue;
            }

            $score = (int)$scores[$category];
            $range = ScoreRange::fromScore($score);
            $distribution[$range->value]++;
        }

        return $distribution;
    }
}
