<?php

declare(strict_types=1);

namespace In2code\Sitescore\Command;

use In2code\Sitescore\Domain\Repository\AnalysisRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
    'sitescore:truncateAnalysis',
    'Truncate the analysis table (delete all cached results)'
)]
class TruncateAnalysisCommand extends Command
{
    public function __construct(
        readonly private AnalysisRepository $analysisRepository,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this->setDescription('Truncate the analysis table (delete all cached results)');
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $helper = $this->getHelper('question');
        $question = new ConfirmationQuestion(
            '<question>Are you sure you want to delete all analysis results? (y/N)</question> ',
            false
        );
        if ($helper->ask($input, $output, $question) === false) {
            $output->writeln('<comment>Aborted.</comment>');
            return parent::SUCCESS;
        }
        $this->analysisRepository->truncate();
        $output->writeln('<info>Successfully truncated analysis table</info>');
        return parent::SUCCESS;
    }
}
