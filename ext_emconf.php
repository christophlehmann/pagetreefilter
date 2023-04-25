<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Pagetree Filter',
    'description' => 'It adds a wizard like the new content element wizard that helps finding content elements and records in the page tree. With a few clicks you know where they are used. You can also see what elements are not used.',
    'category' => 'be',
    'state' => 'stable',
    'clearCacheOnLoad' => 1,
    'author' => 'Christoph Lehmann',
    'author_email' => 'post@christophlehmann.eu',
    'author_company' => '',
    'version' => '2.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ]
];
