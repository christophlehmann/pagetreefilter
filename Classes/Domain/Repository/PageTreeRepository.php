<?php
declare(strict_types=1);

namespace Lemming\PageTreeFilter\Domain\Repository;

use Lemming\PageTreeFilter\Domain\Dto\Result;
use Lemming\PageTreeFilter\Middleware\PageTreeFilterMiddleware;
use Psr\Http\Message\ServerRequestInterface;

class PageTreeRepository extends \TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository
{
    public function fetchFilteredTree(
        string $searchFilter,
        array $allowedMountPointPageIds,
        string $additionalWhereClause
    ): array {
        $result = $this->getResult();
        if ($result) {
            $searchFilter = $result->getFilter()->getRemainingSearchQuery();
            $whereClause = $result->getRecordUids() === [] ? ' AND 1=0' : sprintf(' AND uid IN (%s)', implode(',', $result->getRecordUids()));
            $additionalWhereClause .= $whereClause;
        }

        return parent::fetchFilteredTree($searchFilter, $allowedMountPointPageIds, $additionalWhereClause);
    }

    protected function getResult(): ?Result
    {
        return $this->getServerRequest()->getAttribute(PageTreeFilterMiddleware::ATTRIBUTE);
    }

    protected function getServerRequest(): ServerRequestInterface
    {
        return $GLOBALS['TYPO3_REQUEST'];
    }
}
