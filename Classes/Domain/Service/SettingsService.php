<?php

declare(strict_types=1);

namespace In2code\Sitescore\Domain\Service;

use In2code\Sitescore\Domain\Model\Setting;
use In2code\Sitescore\Domain\Model\SettingAccess;
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
    public function getSettingsForView(SettingAccess $access): array
    {
        $moduleSettings = $this->settingsRepository->findAll();
        $rows = [];
        foreach ($this->getAccessibleSettings($access) as $setting) {
            $moduleValue = $moduleSettings[$setting->key] ?? '';
            $extensionConfigurationValue = ConfigurationUtility::getExtensionConfigurationByKey($setting->key);
            $isPassword = $setting->isPassword();
            $rows[] = [
                'key' => $setting->key,
                'type' => $setting->type,
                'options' => $setting->options,
                'environmentVariable' => $setting->environmentVariable,
                'lockedByEnvironment' => $setting->isLockedByEnvironment(),
                'value' => $isPassword ? '' : $moduleValue,
                'fallback' => $isPassword ? '' : $this->getFallbackValue($setting, $extensionConfigurationValue),
                'source' => $this->getSource($setting, $moduleValue, $extensionConfigurationValue),
                'maskedValue' => $isPassword
                    ? $this->getMaskedEffectiveValue($setting, $moduleValue ?: $extensionConfigurationValue)
                    : '',
            ];
        }
        return $rows;
    }

    /**
     * @return string[] keys of settings with invalid values that were not saved
     */
    public function save(array $input, array $removeSecrets, SettingAccess $access): array
    {
        $moduleSettings = $this->settingsRepository->findAll();
        $invalidKeys = [];
        $submittedSettings = array_filter(
            $this->getEditableSettings($access),
            static fn(Setting $setting): bool => array_key_exists($setting->key, $input)
        );
        foreach ($submittedSettings as $setting) {
            $value = is_string($input[$setting->key]) ? trim($input[$setting->key]) : null;
            if ($value === null || $setting->isValidValue($value) === false) {
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
    protected function getAccessibleSettings(SettingAccess $access): array
    {
        return array_filter(
            $this->settingsDefinition->getSettings(),
            static fn(Setting $setting): bool => $setting->isAccessibleFor($access)
        );
    }

    /**
     * @return Setting[]
     */
    protected function getEditableSettings(SettingAccess $access): array
    {
        return array_filter(
            $this->getAccessibleSettings($access),
            static fn(Setting $setting): bool => $setting->isLockedByEnvironment() === false
        );
    }

    /**
     * An empty secret field keeps the stored secret, so it never has to be sent to the browser
     */
    protected function applySecret(array $moduleSettings, Setting $setting, string $value, bool $remove): array
    {
        if ($value !== '') {
            $moduleSettings[$setting->key] = $value;
        } elseif ($remove) {
            unset($moduleSettings[$setting->key]);
        }
        return $moduleSettings;
    }

    /**
     * "0" is a valid value (e.g. for booleans), so only an empty string falls back to the default
     */
    protected function getFallbackValue(Setting $setting, string $extensionConfigurationValue): string
    {
        $value = $extensionConfigurationValue;
        if ($value === '') {
            $value = $setting->default;
        }
        return $value;
    }

    protected function getSource(Setting $setting, string $moduleValue, string $extensionConfigurationValue): string
    {
        $source = self::SOURCE_NONE;
        if ($setting->isLockedByEnvironment()) {
            $source = self::SOURCE_ENVIRONMENT;
        } elseif ($moduleValue !== '') {
            $source = self::SOURCE_MODULE;
        } elseif ($extensionConfigurationValue !== '') {
            $source = self::SOURCE_EXTENSION_CONFIGURATION;
        }
        return $source;
    }

    protected function getMaskedEffectiveValue(Setting $setting, string $storedValue): string
    {
        $value = (string)getenv($setting->environmentVariable) ?: $storedValue;
        $masked = '';
        if (strlen($value) >= self::MINIMUM_SECRET_LENGTH_FOR_HINT) {
            $masked = self::MASK . substr($value, -self::VISIBLE_SECRET_CHARACTERS);
        } elseif ($value !== '') {
            $masked = self::MASK;
        }
        return $masked;
    }
}
