<?php

/**
 * Extension Manager/Repository config file for ext "suisse_rugby".
 */
$EM_CONF[$_EXTKEY] = [
    'title' => 'Suisse Rugby',
    'description' => '',
    'category' => 'templates',
    'constraints' => [
        'depends' => [
            'bootstrap_package' => '11.0.0-11.0.99',
        ],
        'conflicts' => [
        ],
    ],
    'autoload' => [
        'psr-4' => [
            'MaurizioMonticelli\\SuisseRugby\\' => 'Classes',
        ],
    ],
    'state' => 'stable',
    'uploadfolder' => 0,
    'createDirs' => '',
    'clearCacheOnLoad' => 1,
    'author' => 'Maurizio Monticelli',
    'author_email' => 'rugbymauri@gmail.com',
    'author_company' => 'Maurizio Monticelli',
    'version' => '1.0.0',
];
