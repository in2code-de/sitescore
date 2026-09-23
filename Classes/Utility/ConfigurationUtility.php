<?php

declare(strict_types=1);

namespace In2code\Sitescore\Utility;

use In2code\Sitescore\Domain\Repository\SettingsRepository;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConfigurationUtility
{
    public const DEFAULT_GEMINI_MODEL = 'gemini-3.5-flash-lite:generateContent';
    public const DEFAULT_THINKING_LEVEL = 'medium';
    public const THINKING_LEVELS = ['minimal', 'low', 'medium', 'high'];
    public const ENVIRONMENT_API_KEY = 'GOOGLE_API_KEY';
    public const ENVIRONMENT_GEMINI_MODEL = 'GOOGLE_GEMINI_MODEL';

    /**
     * Settings from the backend module overrule the extension configuration
     */
    public static function getConfigurationByKey(string $key): string
    {
        $value = self::getModuleSettingByKey($key);
        if ($value === '') {
            $value = self::getExtensionConfigurationByKey($key);
        }
        return $value;
    }

    public static function getExtensionConfigurationByKey(string $key): string
    {
        $configuration = GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('sitescore');
        return (string)($configuration[$key] ?? '');
    }

    public static function getApiKey(): string
    {
        return getenv(self::ENVIRONMENT_API_KEY) ?: self::getConfigurationByKey('apiKey');
    }

    public static function getModel(): string
    {
        return getenv(self::ENVIRONMENT_GEMINI_MODEL) ?: self::getConfigurationByKey('geminiModel') ?: self::DEFAULT_GEMINI_MODEL;
    }

    public static function getThinkingLevel(): string
    {
        return self::getConfigurationByKey('thinkingLevel') ?: self::DEFAULT_THINKING_LEVEL;
    }

    /**
     * Database may not be available (e.g. install tool), fall back to the extension configuration then
     */
    protected static function getModuleSettingByKey(string $key): string
    {
        try {
            $value = GeneralUtility::makeInstance(SettingsRepository::class)->findByKey($key);
        } catch (\Throwable) {
            $value = '';
        }
        return $value;
    }
}
