<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Pagetree Filter',
    'description' => 'Filter the pagetree for records and content elements',
    'category' => 'be',
    'state' => 'beta',
    'clearCacheOnLoad' => 1,
    'author' => 'Christoph Lehmann',
    'author_email' => 'post@christophlehmann.eu',
    'author_company' => '',
    'version' => '0.0.1',
    'constraints' => [
        'depends' => [
            'typo3' => '10.4.0-11.3.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ]
];
