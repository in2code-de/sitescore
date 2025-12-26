<?php

declare(strict_types=1);

namespace In2code\Sitescore\Domain\Repository\Llm;

interface RepositoryInterface
{
    public function checkApiKey(): void;
    public function getApiUrl(): string;
    public function getOptions(string $html, string $pageTitle): array;
    public function analyzePageContent(string $html, string $pageTitle): array;
}
