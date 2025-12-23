<?php

declare(strict_types=1);

namespace In2code\Sitescore\Utility;

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConfigurationUtility
{
    public static function getConfigurationByKey(string $key): string
    {
        $configuration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('sitescore');
        return $configuration[$key] ?? '';
    }

    public static function getApiKey(): string
    {
        return getenv('GOOGLE_API_KEY') ?: self::getConfigurationByKey('apiKey') ?: '';
    }
}
