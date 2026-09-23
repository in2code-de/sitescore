<?php

use In2code\Sitescore\Controller\SettingsModuleController;
use TYPO3\CMS\Core\Information\Typo3Version;

/**
 * The module "in2code_ai" is defined identically in all in2code AI extensions (alternative, imager, sitescore, texter),
 * so each extension can be installed standalone while all of them share one "AI" module in the backend:
 * - TYPO3 13: main module "AI" in the module menu, one entry per extension below
 * - TYPO3 14: module "Admin Tools > AI" with a card based overview of the extensions (showSubmoduleOverview)
 * Keep this definition in sync with the other extensions.
 */
$isTypo3Version14 = (new Typo3Version())->getMajorVersion() >= 14;

return [
    'in2code_ai' => $isTypo3Version14
        ? [
            'parent' => 'admin',
            'access' => 'user',
            'path' => '/module/in2code-ai',
            'iconIdentifier' => 'actions-lightbulb-on',
            'labels' => [
                'title' => 'LLL:EXT:sitescore/Resources/Private/Language/Backend/locallang_module.xlf:mainmodule.title',
            ],
            'appearance' => [
                'dependsOnSubmodules' => true,
            ],
            'showSubmoduleOverview' => true,
        ]
        : [
            'labels' => [
                'title' => 'LLL:EXT:sitescore/Resources/Private/Language/Backend/locallang_module.xlf:mainmodule.title',
            ],
            'iconIdentifier' => 'actions-lightbulb-on',
            'position' => ['after' => 'file'],
        ],
    'in2code_ai_sitescore' => [
        'parent' => 'in2code_ai',
        'access' => 'user',
        'path' => '/module/in2code-ai/sitescore',
        'icon' => 'EXT:sitescore/Resources/Public/Icons/Extension.svg',
        'labels' => [
            'title' => 'LLL:EXT:sitescore/Resources/Private/Language/Backend/locallang_module.xlf:module.title',
            'description' => 'LLL:EXT:sitescore/Resources/Private/Language/Backend/locallang_module.xlf:module.description',
            'shortDescription' => 'LLL:EXT:sitescore/Resources/Private/Language/Backend/locallang_module.xlf:module.shortDescription',
        ],
        'routes' => [
            '_default' => [
                'target' => SettingsModuleController::class . '::indexAction',
            ],
            'save' => [
                'target' => SettingsModuleController::class . '::saveAction',
                'methods' => ['POST'],
            ],
        ],
    ],
];
