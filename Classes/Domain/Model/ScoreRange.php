<?php

declare(strict_types=1);

namespace In2code\Sitescore\Domain\Model;

/**
 * Score range classification with thresholds and colors
 * Centralizes score range logic to avoid hardcoded values
 */
enum ScoreRange: string
{
    case HIGH = 'high';
    case MEDIUM = 'medium';
    case LOW = 'low';

    protected const THRESHOLD_HIGH = 75;
    protected const THRESHOLD_MEDIUM = 50;
    protected const THRESHOLD_LOW = 0;
    protected const COLOR_HIGH = '#28a745';
    protected const COLOR_MEDIUM = '#ffc107';
    protected const COLOR_LOW = '#dc3545';
    protected const CSS_CLASS_HIGH = 'score-high';
    protected const CSS_CLASS_MEDIUM = 'score-medium';
    protected const CSS_CLASS_LOW = 'score-low';

    public function getMinScore(): int
    {
        return match ($this) {
            self::HIGH => self::THRESHOLD_HIGH,
            self::MEDIUM => self::THRESHOLD_MEDIUM,
            self::LOW => self::THRESHOLD_LOW,
        };
    }

    public function getMaxScore(): int
    {
        return match ($this) {
            self::HIGH => 100,
            self::MEDIUM => self::THRESHOLD_HIGH - 1,
            self::LOW => self::THRESHOLD_MEDIUM - 1,
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::HIGH => self::COLOR_HIGH,
            self::MEDIUM => self::COLOR_MEDIUM,
            self::LOW => self::COLOR_LOW,
        };
    }

    public function getCssClass(): string
    {
        return match ($this) {
            self::HIGH => self::CSS_CLASS_HIGH,
            self::MEDIUM => self::CSS_CLASS_MEDIUM,
            self::LOW => self::CSS_CLASS_LOW,
        };
    }

    public function getLabel(): string
    {
        return match ($this) {
            self::HIGH => 'Excellent (≥' . self::THRESHOLD_HIGH . '%)',
            self::MEDIUM => 'Good (' . self::THRESHOLD_MEDIUM . '-' . (self::THRESHOLD_HIGH - 1) . '%)',
            self::LOW => 'Needs Improvement (<' . self::THRESHOLD_MEDIUM . '%)',
        };
    }

    public static function fromScore(int $score): self
    {
        return match (true) {
            $score >= self::THRESHOLD_HIGH => self::HIGH,
            $score >= self::THRESHOLD_MEDIUM => self::MEDIUM,
            default => self::LOW,
        };
    }

    public static function all(): array
    {
        return [self::HIGH, self::MEDIUM, self::LOW];
    }
}
