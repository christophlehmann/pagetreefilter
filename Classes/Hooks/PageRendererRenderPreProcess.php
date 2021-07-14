<?php

namespace Lemming\PageTreeFilter\Hooks;

use Lemming\PageTreeFilter\Utility\ConfigurationUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Page\PageRenderer;

final class PageRendererRenderPreProcess
{
    public function addRequireJsModule(array $params, PageRenderer $pageRenderer): void
    {
        if (
            ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
            && ConfigurationUtility::isWizardEnabled()
        ) {
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/Pagetreefilter/PageTreeFilter');
        }
    }
}
