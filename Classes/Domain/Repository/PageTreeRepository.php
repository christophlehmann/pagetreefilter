<?php
declare(strict_types=1);

namespace Lemming\PageTreeFilter\Domain\Repository;

use Lemming\PageTreeFilter\Utility\ConfigurationUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class PageTreeRepository extends \TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository
{
    public static array $filteredPageUids = [];

    public static bool $filterErrorneous = false;

    protected ?string $filterTable;

    protected array $filterConstraints = [];

    protected const ALLOWED_TABLE_FIELDS = [
        'tt_content:CType',
        'tt_content:list_type',
    ];
    // allowed fields, regardless of table
    protected const ALLOWED_FIELDS = [
        'uid',
    ];

    public function fetchFilteredTree(string $searchFilter, array $allowedMountPointPageIds, string $additionalWhereClause): array
    {
        if (ConfigurationUtility::isWizardEnabled()) {
            $newSearchFilter = $this->extractConstraints($searchFilter);
            if (!empty($this->filterTable)) {
                $this->validate();

                if (!self::$filterErrorneous) {
                    self::$filteredPageUids = $this->getFilteredPageUids();

                    if (self::$filteredPageUids !== []) {
                        $additionalWhereClause = sprintf('%s AND uid IN (%s)', $additionalWhereClause,
                            implode(',', self::$filteredPageUids));
                        $searchFilter = $newSearchFilter;
                    }
                }
            }
        }

        return parent::fetchFilteredTree($searchFilter, $allowedMountPointPageIds, $additionalWhereClause);
    }

    protected function getFilteredPageUids(): array
    {
        $pageUids = [];

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($this->filterTable);
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class));

        $field = $this->filterTable ===  'pages' ? 'uid' : 'pid';
        $query = $queryBuilder
            ->select($field)
            ->from($this->filterTable)
            ->groupBy($field);
        if ($this->filterTable === 'pages') {
            $query->addSelect('l10n_parent');
        }

        foreach($this->filterConstraints as $constraint) {
            if (strpos($constraint['value'], '*') !== false) {
                $like = str_replace('*', '', $constraint['value']);
                $parts = explode('*', $constraint['value']);
                $leftLike = empty($parts[0]) ? '%' : '';
                $rightLike = empty(array_pop($parts)) ? '%' : '';
                $query->andWhere(
                    $queryBuilder->expr()->like(
                        $constraint['field'],
                        $queryBuilder->createNamedParameter($leftLike . $queryBuilder->escapeLikeWildcards($like) . $rightLike)
                    )
                );
            } else {
                if (empty($constraint['value']) && $this->isNullableColumn($queryBuilder, $constraint['field'])) {
                    $query->andWhere(
                        $queryBuilder->expr()->or(
                            $queryBuilder->expr()->isNull($constraint['field']),
                            $queryBuilder->expr()->eq($constraint['field'], $queryBuilder->createNamedParameter(''))
                        )
                    );
                } else {
                    $query->andWhere(
                        $queryBuilder->expr()->eq($constraint['field'], $queryBuilder->createNamedParameter($constraint['value']))
                    );
                }
            }
        }

        $rows = $query->executeQuery()->fetchAllAssociative();
        foreach ($rows as $row) {
            if ($this->filterTable === 'pages' && $row['l10n_parent'] > 0) {
                $pageUids[] = $row['l10n_parent'];
            } else {
                $pageUids[] = $row[$field];
            }
        }
        foreach ($rows as $row) {
            $pageUids[] = $row[$field];
        }

        return $pageUids;
    }

    protected function extractConstraints(string $searchFilter): string
    {
        $remainingSearchFilterParts = [];
        foreach(GeneralUtility::trimExplode(' ', $searchFilter, true) as $queryPart) {
            $filter = GeneralUtility::trimExplode('=', $queryPart);
            if (count($filter) == 2) {
                switch ($filter[0]) {
                    case 'table':
                        $this->filterTable = $filter[1];
                        break;
                    default:
                        $this->filterConstraints[] = [
                            'field' => $filter[0],
                            'value' => $filter[1]
                        ];
                }
            } else {
                $remainingSearchFilterParts[] = $queryPart;
            }
        }

        return implode(' ', $remainingSearchFilterParts);
    }

    protected function validate()
    {
        $backendUser = $this->getBackendUser();
        if (!isset($GLOBALS['TCA'][$this->filterTable])) {
            self::$filterErrorneous = true;
        }
        if (!$backendUser->isAdmin() && !$backendUser->check('tables_select', $this->filterTable)) {
            self::$filterErrorneous = true;
        }
        /** @var \TYPO3\CMS\Core\Database\Connection $connection */
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->filterTable);
        foreach($this->filterConstraints as $constraint) {
            if (!isset($GLOBALS['TCA'][$this->filterTable]['columns'][$constraint['field']])) {
                // only if admin or field in ALLOWED_FIELDS: field can also be used if not in TCA, but exists in table
                if (($backendUser->isAdmin() || in_array($constraint['field'], self::ALLOWED_FIELDS))
                    && in_array($constraint['field'], array_keys($connection->createSchemaManager()->listTableColumns($this->filterTable)))
                ) {
                    // all good for this constraint, keep going
                    continue;
                } else {
                    self::$filterErrorneous = true;
                    // filter error - no need to check further
                    return;
                }
            }
            $tableField = $this->filterTable . ':' . $constraint['field'];
            if (
                !$backendUser->isAdmin() &&
                !in_array($tableField, self::ALLOWED_TABLE_FIELDS) &&
                !$backendUser->check('non_exclude_fields', $tableField)
            ) {
                self::$filterErrorneous = true;
                return;
            }
        }
    }

    protected function isNullableColumn(QueryBuilder $queryBuilder, string $column): bool
    {
        $schemaManager = $queryBuilder->getConnection()->createSchemaManager();
        return !$schemaManager->introspectTable($this->filterTable)->getColumn($column)->getNotnull();
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
