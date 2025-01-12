<?php

declare(strict_types=1);

namespace Lemming\PageTreeFilter\EventListener;

use Lemming\PageTreeFilter\Utility\RequestUtility;
use TYPO3\CMS\Backend\View\Event\PageContentPreviewRenderingEvent;

final class PageContentPreviewEventListener
{
    public function __invoke(PageContentPreviewRenderingEvent $event): void
    {
        $result = RequestUtility::getResult();
        if ($result?->isValidFilter() && in_array($event->getRecord()['uid'], $result->getRecordUids() ?? [])) {
            $event->setPreviewContent('<span id="tx-pagetreefilter-highlight" class="tx-pagetreefilter-highlight-ce"></span>' . $event->getPreviewContent());
        }
    }
}
