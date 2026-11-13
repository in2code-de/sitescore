<?php

declare(strict_types=1);

namespace In2code\Sitescore\Domain\Model;

enum SettingAccess: int
{
    case User = 0;
    case Admin = 1;
    case SystemMaintainer = 2;
}
