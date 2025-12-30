<?php

declare(strict_types=1);

namespace In2code\Sitescore\Utility;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

class BackendUserUtility
{
    private const SESSION_KEY = 'tx_sitescore_collapsed';

    public static function setCollapsedState(bool $collapsed): void
    {
        self::getBackendUser()->setAndSaveSessionData(self::SESSION_KEY, $collapsed);
    }

    public static function isCollapsed(): bool
    {
        return (bool)(self::getBackendUser()->getSessionData(self::SESSION_KEY) ?? false);
    }

    public static function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}