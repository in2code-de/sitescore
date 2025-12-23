<?php

declare(strict_types=1);

namespace In2code\Sitescore\Utility;

class BackendUserUtility
{
    private const SESSION_KEY = 'tx_sitescore_collapsed';

    public static function setCollapsedState(bool $collapsed): void
    {
        $GLOBALS['BE_USER']->setAndSaveSessionData(self::SESSION_KEY, $collapsed);
    }

    public static function isCollapsed(): bool
    {
        return (bool)($GLOBALS['BE_USER']->getSessionData(self::SESSION_KEY) ?? false);
    }
}