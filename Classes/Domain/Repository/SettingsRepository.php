<?php

declare(strict_types=1);

namespace In2code\Sitescore\Domain\Repository;

use TYPO3\CMS\Core\Registry;

class SettingsRepository
{
    private const REGISTRY_NAMESPACE = 'tx_sitescore';
    private const REGISTRY_KEY = 'settings';

    public function __construct(
        private readonly Registry $registry,
    ) {
    }

    /**
     * @return array<string, string>
     */
    public function findAll(): array
    {
        $settings = $this->registry->get(self::REGISTRY_NAMESPACE, self::REGISTRY_KEY, []);
        return is_array($settings) ? $settings : [];
    }

    public function findByKey(string $key): ?string
    {
        $value = $this->findAll()[$key] ?? null;
        return $value === null ? null : (string)$value;
    }

    /**
     * @param array<string, string> $settings
     */
    public function persist(array $settings): void
    {
        $this->registry->set(self::REGISTRY_NAMESPACE, self::REGISTRY_KEY, $settings);
    }
}
