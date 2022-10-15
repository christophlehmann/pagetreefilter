<?php
declare(strict_types=1);

namespace Lemming\PageTreeFilter\Hooks;

use Lemming\PageTreeFilter\Utility\ConfigurationUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;

final class PageRendererRenderPreProcess
{
    public function addRequireJsModule(array $params, PageRenderer $pageRenderer): void
    {
        if (
            ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            && !is_null($GLOBALS['TYPO3_REQUEST']->getAttribute('applicationType'))
            && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend()
            && ConfigurationUtility::isWizardEnabled()
        ) {
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/Pagetreefilter/PageTreeFilter');
            $labelPrefix = 'LLL:EXT:pagetreefilter/Resources/Private/Language/locallang.xlf:';
            $pageRenderer->addInlineLanguageLabelArray([
                'pagetreefilter_wizard_title' => $this->getLanguageService()->sL($labelPrefix . 'wizard_title'),
                'pagetreefilter_button_title' => $this->getLanguageService()->sL($labelPrefix . 'filter_button_title'),
                'pagetreefilter_wizard_submit_button' => $this->getLanguageService()->sL($labelPrefix . 'wizard_submit_button'),
                'pagetreefilter_wizard_input_placeholder' => $this->getLanguageService()->sL($labelPrefix . 'wizard_input_placeholder'),
            ]);
        }
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
