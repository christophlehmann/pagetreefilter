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
use Doctrine\DBAL\Connection;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\DataHandling\PlainDataResolver;
use TYPO3\CMS\Core\Versioning\VersionState;

class PageTreeRepository extends \TYPO3\CMS\Backend\Tree\Repository\PageTreeRepository
{
    public static $filteredPageUids = [];

    public static $filterErrorneous = false;

    protected $filterTable;

    protected $filterConstraints = [];

    // @todo: Use predefined list from core. Where is it?
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
        $langUidArray = [0];
        //Render language uid should used in the page tree from typoscript
        $langUidArray  = ConfigurationUtility::usedLanguagesInPageTree();

        if (ConfigurationUtility::isWizardEnabled()) {
            $newSearchFilter = $this->extractConstraints($searchFilter);
            if ($this->filterTable) {
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

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        if (!empty($this->additionalQueryRestrictions)) {
            foreach ($this->additionalQueryRestrictions as $additionalQueryRestriction) {
                $queryBuilder->getRestrictions()->add($additionalQueryRestriction);
            }
        }

        $expressionBuilder = $queryBuilder->expr();

        if ($this->currentWorkspace === 0) {
            // Only include ws_id=0
            $workspaceIdExpression = $expressionBuilder->eq('t3ver_wsid', 0);
        } else {
            // Include live records PLUS records from the given workspace
            $workspaceIdExpression = $expressionBuilder->in(
                't3ver_wsid',
                [0, $this->currentWorkspace]
            );
        }

        $queryBuilder = $queryBuilder
            ->select(...$this->fields)
            ->from('pages')
            ->where(
            // Only show records in default language
                $queryBuilder->expr()->in('sys_language_uid',$queryBuilder->createNamedParameter($langUidArray, Connection::PARAM_INT_ARRAY)),
                $workspaceIdExpression,
                QueryHelper::stripLogicalOperatorPrefix($additionalWhereClause)
            );

        $searchParts = $expressionBuilder->orX();
        if (is_numeric($searchFilter) && $searchFilter > 0) {
            $searchParts->add(
                $expressionBuilder->eq('uid', $queryBuilder->createNamedParameter($searchFilter, \PDO::PARAM_INT))
            );
        }
        $searchFilter = '%' . $queryBuilder->escapeLikeWildcards($searchFilter) . '%';

        $searchWhereAlias = $expressionBuilder->orX(
            $expressionBuilder->like(
                'nav_title',
                $queryBuilder->createNamedParameter($searchFilter, \PDO::PARAM_STR)
            ),
            $expressionBuilder->like(
                'title',
                $queryBuilder->createNamedParameter($searchFilter, \PDO::PARAM_STR)
            )
        );
        $searchParts->add($searchWhereAlias);

        $queryBuilder->andWhere($searchParts);
        $pageRecords = $queryBuilder
            ->execute()
            ->fetchAll();

        $itemUid = [];
        foreach ($pageRecords as $key => $value){
            if($value['l10n_parent'] == 0){
                $itemUid[] = $value['uid'];
            }else{
                $itemUid[]= $value['l10n_parent'];
            }
        }

        $queryBuilderTranlated = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('pages');
        $queryBuilderTranlated->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class));

        $queryBuilderTranlated = $queryBuilderTranlated
            ->select(...$this->fields)
            ->from('pages')
            ->where(
                $queryBuilderTranlated->expr()->in('uid',$queryBuilderTranlated->createNamedParameter($itemUid, Connection::PARAM_INT_ARRAY)),
            );

        $pageRecords = $queryBuilderTranlated->execute()->fetchAllAssociative();

        $livePagePids = [];
        if ($this->currentWorkspace !== 0 && !empty($pageRecords)) {
            $livePageIds = [];
            foreach ($pageRecords as $pageRecord) {
                $livePageIds[] = (int)$pageRecord['uid'];
                $livePagePids[(int)$pageRecord['uid']] = (int)$pageRecord['pid'];
                if ((int)$pageRecord['t3ver_oid'] > 0) {
                    $livePagePids[(int)$pageRecord['t3ver_oid']] = (int)$pageRecord['pid'];
                }
                if ((int)$pageRecord['t3ver_state'] === VersionState::MOVE_PLACEHOLDER) {
                    $movePlaceholderData[$pageRecord['t3ver_move_id']] = [
                        'pid' => (int)$pageRecord['pid'],
                        'sorting' => (int)$pageRecord['sorting']
                    ];
                }
            }
            // Resolve placeholders of workspace versions
            $resolver = GeneralUtility::makeInstance(
                PlainDataResolver::class,
                'pages',
                $livePageIds
            );
            $resolver->setWorkspaceId($this->currentWorkspace);
            $resolver->setKeepDeletePlaceholder(false);
            $resolver->setKeepMovePlaceholder(false);
            $resolver->setKeepLiveIds(false);
            $recordIds = $resolver->get();

            $pageRecords = [];
            if (!empty($recordIds)) {
                $queryBuilder->getRestrictions()->removeAll();
                $queryBuilder
                    ->select(...$this->fields)
                    ->from('pages')
                    ->where(
                        $queryBuilder->expr()->in('uid', $queryBuilder->createNamedParameter($recordIds, Connection::PARAM_INT_ARRAY))
                    );
                $queryBuilder->andWhere($searchParts);
                $pageRecords = $queryBuilder
                    ->execute()
                    ->fetchAll();
            }
        }

        $pages = [];
        foreach ($pageRecords as $pageRecord) {
            // In case this is a record from a workspace
            // The uid+pid of the live-version record is fetched
            // This is done in order to avoid fetching records again (e.g. via BackendUtility::workspaceOL()
            if ((int)$pageRecord['t3ver_oid'] > 0) {
                // This probably should also remove the live version
                if ((int)$pageRecord['t3ver_state'] === VersionState::DELETE_PLACEHOLDER) {
                    continue;
                }
                // When a move pointer is found, the pid+sorting of the MOVE_PLACEHOLDER should be used (this is the
                // workspace record holding this information), also the t3ver_state is set to the MOVE_PLACEHOLDER
                // because the record is then added
                if ((int)$pageRecord['t3ver_state'] === VersionState::MOVE_POINTER && !empty($movePlaceholderData[$pageRecord['t3ver_oid']])) {
                    $parentPageId = (int)$movePlaceholderData[$pageRecord['t3ver_oid']]['pid'];
                    $pageRecord['sorting'] = (int)$movePlaceholderData[$pageRecord['t3ver_oid']]['sorting'];
                    $pageRecord['t3ver_state'] = VersionState::MOVE_PLACEHOLDER;
                } else {
                    // Just a record in a workspace (not moved etc)
                    $parentPageId = (int)$livePagePids[$pageRecord['t3ver_oid']];
                }
                // this is necessary so the links to the modules are still pointing to the live IDs
                $pageRecord['uid'] = (int)$pageRecord['t3ver_oid'];
                $pageRecord['pid'] = $parentPageId;
            }
            $pages[(int)$pageRecord['uid']] = $pageRecord;
        }
        unset($pageRecords);

        $pages = $this->filterPagesOnMountPoints($pages, $allowedMountPointPageIds);



        $groupedAndSortedPagesByPid = $this->groupAndSortPages($pages);

        $this->fullPageTree = [
            'uid' => 0,
            'title' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'] ?: 'TYPO3'
        ];
        $this->addChildrenToPage($this->fullPageTree, $groupedAndSortedPagesByPid);

        return $this->fullPageTree;
    }

    /**
     * @return array
     */
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
                        $queryBuilder->expr()->orX(
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

        $rows = $query->execute()->fetchAll();
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
        $connection = $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($this->filterTable);
        foreach($this->filterConstraints as $constraint) {
            if (!isset($GLOBALS['TCA'][$this->filterTable]['columns'][$constraint['field']])) {
                // only if admin or field in ALLOWED_FIELDS: field can also be used if not in TCA, but exists in table
                if (($backendUser->isAdmin() || in_array($constraint['field'], self::ALLOWED_FIELDS))
                    && in_array($constraint['field'], array_keys($connection->getSchemaManager()->listTableColumns($this->filterTable)))
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
        $schemaManager = $queryBuilder->getConnection()->getSchemaManager();
        return !$schemaManager->listTableDetails($this->filterTable)->getColumn($column)->getNotnull();
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
