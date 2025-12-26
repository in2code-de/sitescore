<?php

declare(strict_types=1);

namespace In2code\Sitescore\Command;

use In2code\Sitescore\Domain\Service\AnalysisService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Site\SiteFinder;

#[AsCommand(
    'sitescore:analyzePage',
    'Analyze a page with AI and save results to database'
)]
class AnalyzePageCommand extends Command
{
    public function __construct(
        readonly private AnalysisService $analysisService,
        readonly private PageRepository $pageRepository,
        readonly private SiteFinder $siteFinder,
        readonly private ConnectionPool $connectionPool,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setDescription('Analyze a page with AI and save results to database');
        $this->addArgument('pageId', InputArgument::REQUIRED, 'Page identifier to analyze');
        $this->addArgument(
            'recursion',
            InputArgument::OPTIONAL,
            'Recursion depth (0: current page, 1: current and one level below, 999: all subpages)',
            0
        );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $pageLanguageCombinations = $this->getPageLanguageCombinations(
            (int)$input->getArgument('pageId'),
            (int)$input->getArgument('recursion')
        );
        $progressBar = new ProgressBar($output, count($pageLanguageCombinations));
        $progressBar->start();
        foreach ($pageLanguageCombinations as $combination) {
            try {
                $this->analysisService->analyzePage($combination['pageId'], $combination['languageId']);
            } catch (\Throwable $exception) {
                // Silent fail - continue with next combination
            }
            $progressBar->advance();
        }
        $progressBar->finish();
        $output->writeln('');
        return parent::SUCCESS;
    }

    protected function getPageLanguageCombinations(int $pageId, int $recursion): array
    {
        $pageIds = $this->pageRepository->getPageIdsRecursive([$pageId], $recursion);
        $site = $this->siteFinder->getSiteByPageId($pageId);

        $combinations = [];
        foreach ($pageIds as $pid) {
            $pageRecord = $this->getPageRecord($pid);
            if ($pageRecord !== []) {
                foreach ($site->getLanguages() as $language) {
                    $languageId = $language->getLanguageId();
                    if ($this->isPageAvailableInLanguage($pageRecord, $languageId)) {
                        $combinations[] = ['pageId' => $pid, 'languageId' => $languageId];
                    }
                }
            }
        }
        return $combinations;
    }

    protected function getPageRecord(int $pageId): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        return $queryBuilder
            ->select('*')
            ->from('pages')
            ->where($queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)))
            ->executeQuery()
            ->fetchAssociative() ?: [];
    }

    protected function isPageAvailableInLanguage(array $pageRecord, int $languageId): bool
    {
        $l18nCfg = (int)($pageRecord['l18n_cfg'] ?? 0);
        if ($languageId === 0) {
            return ($l18nCfg & 1) === 0;
        }
        if ($this->hasTranslation($pageRecord['uid'], $languageId) === false) {
            return false;
        }
        return ($l18nCfg & 2) === 0;
    }

    protected function hasTranslation(int $pageId, int $languageId): bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        return $queryBuilder
            ->count('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq('l10n_parent', $queryBuilder->createNamedParameter($pageId, Connection::PARAM_INT)),
                $queryBuilder->expr()->eq('sys_language_uid', $queryBuilder->createNamedParameter($languageId, Connection::PARAM_INT))
            )
            ->executeQuery()
            ->fetchOne() > 0;
    }
}
