<?php

return [
    'backend' => [
        'lemming/pagetreefilter/add-filter' => [
            'target' => \Lemming\PageTreeFilter\Middleware\PageTreeFilterMiddleware::class,
            'after' => [
                'typo3/cms-backend/site-resolver',
                'typo3/cms-backend/authentication'
            ]
        ],
    ],
];
