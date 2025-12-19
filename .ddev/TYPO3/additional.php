<?php
$GLOBALS['TYPO3_CONF_VARS']['SYS']['passwordPolicies']['simple'] = [
    'validators' => [
        \TYPO3\CMS\Core\PasswordPolicy\Validator\CorePasswordValidator::class => [
            'options' => [
                'digitCharacterRequired' => false,
                'lowerCaseCharacterRequired' => false,
                'minimumLength' => 3,
                'specialCharacterRequired' => false,
                'upperCaseCharacterRequired' => false,
            ],
        ],
    ],
];