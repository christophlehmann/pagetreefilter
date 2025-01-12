<?php

declare(strict_types=1);

namespace Lemming\PageTreeFilter\EventListener;

use Lemming\PageTreeFilter\Domain\Dto\Result;
use Lemming\PageTreeFilter\Middleware\PageTreeFilterMiddleware;
use TYPO3\CMS\Backend\Controller\Event\AfterPageTreeItemsPreparedEvent;
use TYPO3\CMS\Backend\Dto\Tree\Label\Label;
use TYPO3\CMS\Core\Localization\LanguageService;

final class AfterPageTreeItemsPreparedEventListener
{
    public function __invoke(AfterPageTreeItemsPreparedEvent $event): void
    {
        $result = $this->getResult($event);
        if ($result) {
            $items = $event->getItems();

            if ($result->isValidFilter()) {
                $label = $this->getLanguageService()->sL('LLL:EXT:pagetreefilter/Resources/Private/Language/locallang.xlf:result_item_label');
                $urlParams = "&table=" . $result->getFilter()->getTable() . "&tx_pagetreefilter[filter]=" . $result->getFilter()->getRawQuery() . '#tx-pagetreefilter-highlight';
                if (count($items) > 1) {
                    $items[0]['identifier'] .= $urlParams;
                }

                foreach ($items as $key => $element) {
                    if (in_array($element['identifier'], $result->getRecordUids())) {
                        $items[$key]['identifier'] .= $urlParams;
                        $items[$key]['labels'][] = new Label(
                            label: $label,
                            color: '#6daae0',
                            priority: 100
                        );
                    }
                }
            } else {
                $items[0]['name'] = sprintf('❌ %s', $this->getLanguageService()->sL('LLL:EXT:pagetreefilter/Resources/Private/Language/locallang.xlf:filter_error'));
            }
            $event->setItems($items);
        }
    }

    protected function getResult(AfterPageTreeItemsPreparedEvent $event): ?Result
    {
        return $event->getRequest()->getAttribute(PageTreeFilterMiddleware::ATTRIBUTE);
    }

    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}
