<?php

$EM_CONF['tm_migration'] = [
    'title' => 'TM Migration Tool',
    'description' => 'TYPO3 extension that brings together the tools used for a major TYPO3 migration.',
    'category' => 'misc',
    'author' => 'Haythem Daoud',
    'author_email' => 'haythem.daoud@toumoro.com',
    'state' => 'stable',
    'version' => '13.4.9',
    'constraints' => [
        'depends' => [
            'a9f/typo3-fractor' => '0.5.0-0.9.0',
            'ssch/typo3-rector' => '2.0.0-3.9.0',
            'wapplersystems/core-upgrader' => 'dev-release/v12 || dev-release/v13',
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
