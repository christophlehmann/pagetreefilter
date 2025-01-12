<?php

declare(strict_types=1);

namespace Lemming\PageTreeFilter\EventListener;

use Lemming\PageTreeFilter\Utility\RequestUtility;
use TYPO3\CMS\Backend\View\Event\ModifyDatabaseQueryForRecordListingEvent;

final class RecordListEventListener
{
    public function __invoke(ModifyDatabaseQueryForRecordListingEvent $event): void
    {
        $result = RequestUtility::getResult();
        if ($result?->isValidFilter() && $result?->getFilter()->getTable() === $event->getTable()
            && $result->getRecordUids() !== []
        ) {
            $queryBuilder = $event->getQueryBuilder();

            // List all filtered records on page 0
            if ($result->getFilter()->getCurrentPage() === 0) {
                $queryBuilder->resetWhere();
                $recordList = $event->getDatabaseRecordList();
                // Add pid field
                $recordList->setFields[$result->getFilter()->getTable()][] = $result->getFilter()->getTable() == 'pages' ? 'uid' : 'pid';
            }

            $queryBuilder->andWhere(
                $queryBuilder->expr()->in('uid', $result->getRecordUids())
            );
        }
    }
}
