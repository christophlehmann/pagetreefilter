<?php
declare(strict_types=1);

namespace Lemming\PageTreeFilter\Validation;

use Lemming\PageTreeFilter\Domain\Dto\Filter;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class FilterValidator
{
    protected const ALLOWED_TABLE_FIELDS = [
        'tt_content:CType',
        'tt_content:list_type',
    ];
    // allowed fields, regardless of table
    protected const ALLOWED_FIELDS = [
        'uid',
    ];

    public function validate(Filter $filter): bool
    {
        $backendUser = $this->getBackendUser();

        if (!isset($GLOBALS['TCA'][$filter->getTable()])) {
            return false;
        }
        if (!$backendUser->isAdmin() && !$backendUser->check('tables_select', $filter->getTable())) {
            return false;
        }

        // Do not allow non-admins to list all records on page 0 (for now)
        if (!$backendUser->isAdmin() && $filter->getCurrentPage() === 0) {
            return false;
        }

        $tableColumns = $this->getTableColumns($filter->getTable());
        foreach ($filter->getConstraints() as $constraint) {
            if ($backendUser->isAdmin()) {
                if (in_array(strtolower($constraint['field']), $tableColumns)) {
                    continue;
                } else {
                    return false;
                }
            }

            $isAlwaysAllowedField = in_array($constraint['field'], self::ALLOWED_FIELDS);
            if ($isAlwaysAllowedField) {
                continue;
            }

            $tcaConfigExists = isset($GLOBALS['TCA'][$filter->getTable()]['columns'][$constraint['field']]);
            if (!$tcaConfigExists) {
                return false;
            }

            $nonExcludeField = $filter->getTable() . ':' . $constraint['field'];
            if (in_array($nonExcludeField, self::ALLOWED_TABLE_FIELDS)) {
                continue;
            }

            if (!$backendUser->check('non_exclude_fields', $nonExcludeField)) {
                return false;
            }
        }

        return true;
    }

    protected function getTableColumns($tableName): array
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable($tableName);
        return array_keys($connection->createSchemaManager()->listTableColumns($tableName));
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}