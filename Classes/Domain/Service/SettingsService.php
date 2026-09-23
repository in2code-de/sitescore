<?php

declare(strict_types=1);

namespace In2code\Sitescore\Domain\Service;

use In2code\Sitescore\Domain\Model\Setting;
use In2code\Sitescore\Domain\Repository\SettingsRepository;
use In2code\Sitescore\Domain\Settings\SettingsDefinition;
use In2code\Sitescore\Utility\ConfigurationUtility;

class SettingsService
{
    public const SOURCE_ENVIRONMENT = 'environment';
    public const SOURCE_MODULE = 'module';
    public const SOURCE_EXTENSION_CONFIGURATION = 'extensionConfiguration';
    public const SOURCE_NONE = 'none';

    private const MASK = '••••••••';
    private const VISIBLE_SECRET_CHARACTERS = 4;
    private const MINIMUM_SECRET_LENGTH_FOR_HINT = 12;

    public function __construct(
        private readonly SettingsDefinition $settingsDefinition,
        private readonly SettingsRepository $settingsRepository,
    ) {
    }

    /**
     * Secrets are never passed to the view, only a masked hint and where the value comes from
     */
    public function getSettingsForView(bool $isAdmin): array
    {
        $moduleSettings = $this->settingsRepository->findAll();
        $rows = [];
        foreach ($this->getAccessibleSettings($isAdmin) as $setting) {
            $moduleValue = $moduleSettings[$setting->key] ?? '';
            $fallbackValue = $this->getFallbackValue($setting);
            $rows[] = [
                'key' => $setting->key,
                'type' => $setting->type,
                'options' => $setting->options,
                'environmentVariable' => $setting->environmentVariable,
                'lockedByEnvironment' => $setting->isLockedByEnvironment(),
                'value' => $setting->isPassword() ? '' : $moduleValue,
                'fallback' => $setting->isPassword() ? '' : $fallbackValue,
                'source' => $this->getSource($setting, $moduleValue),
                'maskedValue' => $setting->isPassword() ? $this->getMaskedEffectiveValue($setting, $moduleValue) : '',
            ];
        }
        return $rows;
    }

    /**
     * @return string[] keys of settings with invalid values that were not saved
     */
    public function save(array $input, array $removeSecrets, bool $isAdmin): array
    {
        $moduleSettings = $this->settingsRepository->findAll();
        $invalidKeys = [];
        $submittedSettings = array_filter(
            $this->getEditableSettings($isAdmin),
            static fn(Setting $setting): bool => array_key_exists($setting->key, $input)
        );
        foreach ($submittedSettings as $setting) {
            $value = trim((string)$input[$setting->key]);
            if ($setting->isValidValue($value) === false) {
                $invalidKeys[] = $setting->key;
            } elseif ($setting->isPassword()) {
                $moduleSettings = $this->applySecret($moduleSettings, $setting, $value, isset($removeSecrets[$setting->key]));
            } else {
                $moduleSettings[$setting->key] = $value;
            }
        }
        $this->settingsRepository->persist($moduleSettings);
        return $invalidKeys;
    }

    /**
     * @return Setting[]
     */
    protected function getAccessibleSettings(bool $isAdmin): array
    {
        return array_filter(
            $this->settingsDefinition->getSettings(),
            static fn(Setting $setting): bool => $setting->adminOnly === false || $isAdmin
        );
    }

    /**
     * @return Setting[]
     */
    protected function getEditableSettings(bool $isAdmin): array
    {
        return array_filter(
            $this->getAccessibleSettings($isAdmin),
            static fn(Setting $setting): bool => $setting->isLockedByEnvironment() === false
        );
    }

    /**
     * An empty secret field keeps the stored secret, so it never has to be sent to the browser
     */
    protected function applySecret(array $moduleSettings, Setting $setting, string $value, bool $remove): array
    {
        if ($remove) {
            unset($moduleSettings[$setting->key]);
        } elseif ($value !== '') {
            $moduleSettings[$setting->key] = $value;
        }
        return $moduleSettings;
    }

    /**
     * "0" is a valid value (e.g. for booleans), so only an empty string falls back to the default
     */
    protected function getFallbackValue(Setting $setting): string
    {
        $value = ConfigurationUtility::getExtensionConfigurationByKey($setting->key);
        if ($value === '') {
            $value = $setting->default;
        }
        return $value;
    }

    protected function getSource(Setting $setting, string $moduleValue): string
    {
        $source = self::SOURCE_NONE;
        if ($setting->isLockedByEnvironment()) {
            $source = self::SOURCE_ENVIRONMENT;
        } elseif ($moduleValue !== '') {
            $source = self::SOURCE_MODULE;
        } elseif (ConfigurationUtility::getExtensionConfigurationByKey($setting->key) !== '') {
            $source = self::SOURCE_EXTENSION_CONFIGURATION;
        }
        return $source;
    }

    protected function getMaskedEffectiveValue(Setting $setting, string $moduleValue): string
    {
        $value = (string)getenv($setting->environmentVariable)
            ?: $moduleValue
            ?: ConfigurationUtility::getExtensionConfigurationByKey($setting->key);
        $masked = '';
        if (strlen($value) >= self::MINIMUM_SECRET_LENGTH_FOR_HINT) {
            $masked = self::MASK . substr($value, -self::VISIBLE_SECRET_CHARACTERS);
        } elseif ($value !== '') {
            $masked = self::MASK;
        }
        return $masked;
    }
}
