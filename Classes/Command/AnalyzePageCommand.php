<?php

declare(strict_types=1);

namespace In2code\Sitescore\Command;

use In2code\Sitescore\Domain\Service\AnalysisService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;

#[AsCommand(
    'sitescore:analyzePage',
    'Analyze a page with AI and save results to database'
)]
class AnalyzePageCommand extends Command
{
    public function __construct(
        readonly private AnalysisService $analysisService,
        readonly private PageRepository $pageRepository,
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
        $pageId = (int)$input->getArgument('pageId');
        $pageIds = $this->getPageIds($pageId, (int)$input->getArgument('recursion'));

        $output->writeln(sprintf('Found %d page(s) to analyze', count($pageIds)));
        $output->writeln('');

        foreach ($pageIds as $index => $pid) {
            $output->writeln(sprintf('[%d/%d] Analyzing page %d...', $index + 1, count($pageIds), $pid));

            try {
                $result = $this->analysisService->analyzePage($pid);
                $output->writeln(sprintf('  ✓ Page title: %s', $result['pageTitle']));
                $output->writeln(sprintf('  ✓ Scores: %d, Suggestions: %d', count($result['scores']), count($result['suggestions'])));
            } catch (\Throwable $exception) {
                $output->writeln(sprintf('  <error>✗ Failed: %s (%d)</error>', $exception->getMessage(), $exception->getCode()));
            }

            $output->writeln('');
        }
        $output->writeln('<info>Task completely finished</info>');
        return parent::SUCCESS;
    }

    private function getPageIds(int $pageId, int $recursion): array
    {
        return $this->pageRepository->getPageIdsRecursive([$pageId], $recursion);
    }
}
