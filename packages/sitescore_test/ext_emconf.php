<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Sitescore Test Sitepackage',
    'description' => 'Test sitepackage to demonstrate LLM repository override with Mistral',
    'category' => 'templates',
    'author' => 'in2code GmbH',
    'author_email' => 'info@in2code.de',
    'state' => 'stable',
    'version' => '1.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.99.99',
            'sitescore' => '1.0.0-1.99.99',
        ],
    ],
];
