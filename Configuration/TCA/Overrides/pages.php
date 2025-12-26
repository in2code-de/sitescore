<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

defined('TYPO3') || die();

$GLOBALS['TCA']['pages']['columns']['tx_sitescore_keyword'] = [
    'label' => 'LLL:EXT:sitescore/Resources/Private/Language/Backend/locallang.xlf:pages.tx_sitescore_keyword',
    'config' => [
        'type' => 'input',
        'size' => 30,
        'max' => 255,
        'eval' => 'trim',
    ],
];

ExtensionManagementUtility::addToAllTCAtypes(
    'pages',
    'tx_sitescore_keyword',
    '',
    'after:description'
);
