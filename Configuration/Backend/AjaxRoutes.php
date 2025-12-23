<?php

use In2code\Sitescore\Controller\AnalysisController;

return [
    'sitescore_analyze' => [
        'path' => '/sitescore/analyze',
        'target' => AnalysisController::class,
        'access' => 'user,group',
        'methods' => ['POST'],
    ],
];
