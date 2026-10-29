<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Compare slider (before/after)',
    'description' => 'Before/after image comparison slider content element for TYPO3. Dependency-free vanilla JavaScript, keyboard-accessible, horizontal or vertical.',
    'category' => 'fe',
    'author' => 'pixelegg',
    'author_email' => 'info@pixelegg.de',
    'author_company' => 'pixelegg Informatik & Design GmbH',
    'state' => 'stable',
    'version' => '2.2.1',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-14.99.99',
            'php' => '8.2.0-8.4.99',
            'fluid_styled_content' => '13.4.0-14.99.99',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
