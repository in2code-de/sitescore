<?php

use In2code\Sitescore\Controller\AnalysisController;
use In2code\Sitescore\Controller\LoadAnalysisController;
use In2code\Sitescore\Controller\ToggleController;

return [
    'sitescore_analyze' => [
        'path' => '/sitescore/analyze',
        'target' => AnalysisController::class,
        'access' => 'user,group',
        'methods' => ['POST'],
    ],
    'sitescore_load' => [
        'path' => '/sitescore/load',
        'target' => LoadAnalysisController::class,
        'access' => 'user,group',
        'methods' => ['GET'],
    ],
    'sitescore_toggle' => [
        'path' => '/sitescore/toggle',
        'target' => ToggleController::class,
        'access' => 'user,group',
        'methods' => ['POST'],
    ],
];
