<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Pagetree Filter',
    'description' => 'It adds a wizard like the new content element wizard that helps finding content elements and records in the page tree. With a few clicks you know where they are used. You can also see what elements are not used.',
    'category' => 'be',
    'state' => 'stable',
    'author' => 'Christoph Lehmann',
    'author_email' => 'post@christophlehmann.eu',
    'author_company' => '',
    'version' => '3.0.0',
    'constraints' => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99'
        ],
        'conflicts' => [],
        'suggests' => [],
    ]
];
