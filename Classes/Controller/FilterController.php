<?php

namespace Lemming\PageTreeFilter\Controller;

use Lemming\PageTreeFilter\Utility\ConfigurationUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FilterController {

    public function fetchFilterAction(ServerRequestInterface $request): ResponseInterface
    {
        if (ConfigurationUtility::isWizardEnabled()) {
            /** @var NewContentElementController $controller */
            $controller = GeneralUtility::makeInstance(NewContentElementController::class);
            /** @var HtmlResponse $htmlResponse */
            $htmlResponse = $controller->wizardAction($request);

            $data = [
                'html' => $htmlResponse->getBody()->getContents(),
                'title' => $this->getLanguageService()->sL('LLL:EXT:pagetreefilter/Resources/Private/Language/locallang.xlf:wizard_title')
            ];

            return new JsonResponse($data);
        }

        return new JsonResponse([], 500);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}