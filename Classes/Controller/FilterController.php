<?php
declare(strict_types=1);

namespace Lemming\PageTreeFilter\Controller;

use Lemming\PageTreeFilter\Utility\ConfigurationUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FilterController {

    public function fetchFilterAction(ServerRequestInterface $request): ResponseInterface
    {
        if (ConfigurationUtility::isWizardEnabled()) {
            /** @var WizardController $controller */
            $controller = GeneralUtility::makeInstance(WizardController::class);
            /** @var HtmlResponse $htmlResponse */
            $htmlResponse = $controller->handleRequest($request);

            return $htmlResponse;
        }

        return new HtmlResponse('Error', 500);
    }
}