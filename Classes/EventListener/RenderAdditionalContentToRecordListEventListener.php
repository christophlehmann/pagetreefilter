<?php

declare(strict_types=1);

namespace Lemming\PageTreeFilter\EventListener;

use Lemming\PageTreeFilter\Utility\RequestUtility;
use TYPO3\CMS\Backend\Controller\Event\RenderAdditionalContentToRecordListEvent;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\Renderer\BootstrapRenderer;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class RenderAdditionalContentToRecordListEventListener
{
    public function __invoke(RenderAdditionalContentToRecordListEvent $event): void
    {
        if (RequestUtility::getResult()?->isValidFilter()) {
            $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, '', 'PageTreeFilter result', ContextualFeedbackSeverity::INFO);
            $renderer = GeneralUtility::makeInstance(BootstrapRenderer::class);
            $event->addContentAbove($renderer->render([$flashMessage]));
        }
    }
}
