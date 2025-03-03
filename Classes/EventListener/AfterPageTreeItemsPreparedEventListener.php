<?php
declare(strict_types=1);

namespace Lemming\PageTreeFilter\EventListener;

use Lemming\PageTreeFilter\Domain\Repository\PageTreeRepository;
use TYPO3\CMS\Backend\Controller\Event\AfterPageTreeItemsPreparedEvent;
use TYPO3\CMS\Backend\Dto\Tree\Label\Label;
use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Localization\LanguageService;

class AfterPageTreeItemsPreparedEventListener
{
    #[AsEventListener]
    public function __invoke(AfterPageTreeItemsPreparedEvent $event)
    {
        $items = $event->getItems();
        if (PageTreeRepository::$filterErrorneous === true) {
            $rootItem = array_pop($items);
            $rootItem['name'] = sprintf('❌ %s', $this->getLanguageService()->sL('LLL:EXT:pagetreefilter/Resources/Private/Language/locallang.xlf:filter_error'));
            $items = [$rootItem];
        } else {
            if (PageTreeRepository::$filteredPageUids !== []) {
                $label = $this->getLanguageService()->sL('LLL:EXT:pagetreefilter/Resources/Private/Language/locallang.xlf:result_item_label');
                foreach($items as $key => $element) {
                    if (in_array($element['identifier'], PageTreeRepository::$filteredPageUids)) {
                        $items[$key]['labels'][] = new Label(
                            label: $label,
                            color: '#6daae0',
                            priority: 100
                        );
                    }
                }
            }
        }
        $event->setItems($items);
    }

    protected function getLanguageService(): ?LanguageService
    {
        return $GLOBALS['LANG'] ?? null;
    }
}