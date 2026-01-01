<?php
$EM_CONF[$_EXTKEY] = [
    'title' => 'Sitescore',
    'description' => 'AI-driven content quality dashboard providing SEO, GEO and accessibility scores directly in the TYPO3 page module',
    'category' => 'plugin',
    'version' => '1.0.0',
    'author' => 'Alex Kellner',
    'author_email' => 'alexander.kellner@in2code.de',
    'author_company' => 'in2code.de',
    'state' => 'stable',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.9.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
