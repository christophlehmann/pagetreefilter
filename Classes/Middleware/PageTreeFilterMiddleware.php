<?php

declare(strict_types=1);

namespace Lemming\PageTreeFilter\Middleware;

use Lemming\PageTreeFilter\Domain\Dto\Filter;
use Lemming\PageTreeFilter\Domain\Dto\Result;
use Lemming\PageTreeFilter\Domain\Repository\Repository;
use Lemming\PageTreeFilter\Utility\ConfigurationUtility;
use Lemming\PageTreeFilter\Validation\FilterValidator;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

class PageTreeFilterMiddleware implements MiddlewareInterface
{
    public const ATTRIBUTE = 'pagetreefilter';

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $filter = $this->createfilterFromRequest($request);
        if (!$filter?->getTable() || !ConfigurationUtility::isWizardEnabled()) {
            return $handler->handle($request);
        }

        $filterValidator = new FilterValidator();
        $isValidFilter = $filterValidator->validate($filter);

        $recordUids = $isValidFilter ? GeneralUtility::makeInstance(Repository::class)->getFilteredRecords($filter) : [];
        $result = new Result($recordUids, $filter, $isValidFilter);
        $request = $request->withAttribute(self::ATTRIBUTE, $result);
        return $handler->handle($request);
    }

    protected function createfilterFromRequest(ServerRequestInterface $request): ?Filter
    {
        $uriPath = $request->getUri()->getPath();
        if ($uriPath === '/typo3/ajax/page/tree/filterData') {
            $rawFilter = $request->getQueryParams()['q'] ?? null;
            if (!empty($rawFilter)) {
                return new Filter($rawFilter);
            }
        } elseif ($uriPath === '/typo3/module/web/layout' || $uriPath === '/typo3/module/web/list') {
            $rawFilter = $request->getQueryParams()['tx_pagetreefilter']['filter'] ?? null;
            $currentPage = $request->getQueryParams()['id'] ?? null;
            if (!empty($rawFilter) && MathUtility::canBeInterpretedAsInteger($currentPage)) {
                return new Filter($rawFilter, true, (int)$currentPage);
            }
        }

        return null;
    }
}
