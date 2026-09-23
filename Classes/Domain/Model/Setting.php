<?php

declare(strict_types=1);

namespace In2code\Sitescore\Domain\Model;

final class Setting
{
    public const TYPE_TEXT = 'text';
    public const TYPE_TEXTAREA = 'textarea';
    public const TYPE_PASSWORD = 'password';
    public const TYPE_SELECT = 'select';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_BOOLEAN = 'boolean';

    /**
     * @param string[] $options allowed values for TYPE_SELECT
     */
    public function __construct(
        public readonly string $key,
        public readonly string $type = self::TYPE_TEXT,
        public readonly string $default = '',
        public readonly array $options = [],
        public readonly string $environmentVariable = '',
        public readonly bool $adminOnly = false,
    ) {
    }

    public function isPassword(): bool
    {
        return $this->type === self::TYPE_PASSWORD;
    }

    public function isLockedByEnvironment(): bool
    {
        return $this->environmentVariable !== '' && (string)getenv($this->environmentVariable) !== '';
    }

    public function isValidValue(string $value): bool
    {
        return match ($this->type) {
            self::TYPE_SELECT => $value === '' || in_array($value, $this->options, true),
            self::TYPE_INTEGER => $value === '' || ctype_digit($value),
            self::TYPE_BOOLEAN => in_array($value, ['', '0', '1'], true),
            default => true,
        };
    }
}
