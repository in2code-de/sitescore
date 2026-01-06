<?php

declare(strict_types=1);

namespace In2code\Sitescore\Widgets\Provider;

use In2code\Sitescore\Domain\Model\ScoreRange;
use In2code\Sitescore\Domain\Repository\AnalysisRepository;
use TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface;

class ScoreDistributionDataProvider implements ChartDataProviderInterface
{
    public function __construct(
        private readonly AnalysisRepository $analysisRepository,
        private readonly string $category,
    ) {}

    public function getChartData(): array
    {
        $distribution = $this->analysisRepository->getScoreDistribution($this->category, 0);

        // Get labels and colors from ScoreRange enum to ensure consistency
        $labels = [];
        $colors = [];
        $data = [];

        foreach (ScoreRange::all() as $range) {
            $labels[] = $range->getLabel();
            $colors[] = $range->getColor();
            $data[] = $distribution[$range->value] ?? 0;
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'backgroundColor' => $colors,
                    'data' => $data,
                ],
            ],
        ];
    }
}
