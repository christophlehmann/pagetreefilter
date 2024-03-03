<?php

defined('TYPO3') or die();

$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository::class] = [
    'className' => \Lemming\PageTreeFilter\Domain\Repository\PageTreeRepository::class
];
$GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Backend\Controller\Page\TreeController::class] = [
    'className' => \Lemming\PageTreeFilter\Controller\TreeController::class
];

$GLOBALS['TYPO3_CONF_VARS']['BE']['stylesheets']['pagetreefilter'] = 'EXT:pagetreefilter/Resources/Public/Css/Backend';