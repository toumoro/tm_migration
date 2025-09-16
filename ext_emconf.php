<?php

$EM_CONF['tm_migration'] = [
    'title' => 'TM Migration Tool',
    'description' => 'TYPO3 extension that brings together the tools used for a major TYPO3 migration.',
    'category' => 'misc',
    'author' => 'Haythem Daoud',
    'author_email' => 'haythem.daoud@toumoro.com',
    'state' => 'stable',
    'version' => '13.4.8',
    'constraints' => [
        'depends' => [
            'a9f/typo3-fractor' => '*',
            'ssch/typo3-rector' => '*',
            'wapplersystems/core-upgrader' => '*'
        ],
        'conflicts' => [],
        'suggests' => [],
    ],
];
