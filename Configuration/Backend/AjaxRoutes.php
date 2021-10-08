<?php

return [
    'pagetreefilter_fetch_filter' => [
        'path' => '/pagetreefilter/fetchfilter',
        'referrer' => 'required,refresh-empty',
        'target' => \Lemming\PageTreeFilter\Controller\WizardController::class . '::handleRequest'
    ],
];
