<?php
declare(strict_types=1);

namespace Lemming\PageTreeFilter\Domain\Repository;

use Lemming\PageTreeFilter\Domain\Dto\Filter;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class Repository
{
    public function getFilteredRecords(Filter $filter): array
    {
        $pageUids = [];
        $isPagesTable = $filter->getTable() === 'pages';

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($filter->getTable());
        $queryBuilder
            ->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class));

        $field = ($isPagesTable || $filter->isHighlighting()) ? 'uid' : 'pid';
        $query = $queryBuilder
            ->select($field)
            ->from($filter->getTable())
            ->groupBy($field);
        if ($isPagesTable) {
            $query->addSelect('l10n_parent');
        }

        foreach ($filter->getConstraints() as $constraint) {
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
                if (empty($constraint['value']) && $this->isNullableColumn($queryBuilder, $constraint['field'], $filter->getTable())) {
                    $query->andWhere(
                        $queryBuilder->expr()->or(
                            $queryBuilder->expr()->isNull($constraint['field']),
                            $queryBuilder->expr()->eq($constraint['field'], $queryBuilder->createNamedParameter(''))
                        )
                    );
                } else {
                    $query->andWhere(
                        $queryBuilder->expr()->eq($constraint['field'],
                            $queryBuilder->createNamedParameter($constraint['value']))
                    );
                }
            }
        }

        $rows = $query->executeQuery()->fetchAllAssociative();
        foreach ($rows as $row) {
            if ($isPagesTable && $row['l10n_parent'] > 0) {
                $pageUids[] = $row['l10n_parent'];
            } else {
                $pageUids[] = $row[$field];
            }
        }

        return $pageUids;
    }

    protected function isNullableColumn(QueryBuilder $queryBuilder, string $column, string $table): bool
    {
        $schemaManager = $queryBuilder->getConnection()->createSchemaManager();
        return !$schemaManager->introspectTable($table)->getColumn($column)->getNotnull();
    }
}
