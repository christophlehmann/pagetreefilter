<?php
declare(strict_types=1);

namespace Lemming\PageTreeFilter\Controller;

use Lemming\PageTreeFilter\Utility\ConfigurationUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class WizardController extends NewContentElementController
{
    /**
     * @var IconRegistry
     */
    protected $iconRegistry;

    protected $unknownContentTypes = [];

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        if (ConfigurationUtility::isWizardEnabled()) {
            $queryParameters = $request->getQueryParams();
            $queryParameters['id'] = ConfigurationUtility::getPageId();
            $requestWithPageId = $request->withQueryParams($queryParameters);

            return parent::handleRequest($requestWithPageId);
        }

        return new HtmlResponse('Error', 500);
    }

    public function getWizards(): array
    {
        $this->disableContentDefenderHook();
        $wizards = parent::getWizards();
        $wizards = $this->appendItemsHavingNoWizardConfiguration($wizards, 'list_type');
        $wizards = $this->appendItemsHavingNoWizardConfiguration($wizards, 'CType');
        ksort($wizards);
        $wizards = $this->keepOnlyListTypeAndCTypeInDefaultValues($wizards);
        $wizards = $this->disableWizardsHavingNoResults($wizards);
        $wizards = $this->appendRecords($wizards);
        $wizards = $this->appendPageTypes($wizards);
        $wizards = $this->appendUnknownContentTypes($wizards);
        $wizards = $this->appendExtended($wizards);


        return $wizards;
    }

    protected function appendItemsHavingNoWizardConfiguration($wizards, $columnName)
    {
        foreach ($GLOBALS['TCA']['tt_content']['columns'][$columnName]['config']['items'] ?? [] as $itemConfiguration) {
            $contentType = $itemConfiguration[1];

            if (empty($contentType) || $contentType === '--div--') {
                continue;
            }

            $authMode = $GLOBALS['TCA']['tt_content']['columns']['list_type']['config']['authMode'];
            if (!$this->getBackendUser()->checkAuthMode('tt_content', $columnName, $contentType, $authMode)) {
                continue;
            }

            $newDefaultValues = $columnName === 'list_type' ? ['CType' => 'list', 'list_type' => $contentType] : ['CType' => $contentType];
            $availableDefaultValues = array_map(function ($wizard) {
                return $wizard['tt_content_defValues'] ?? [];
            }, $wizards);
            if (in_array($newDefaultValues, $availableDefaultValues)) {
                continue;
            }

            $iconIdentifier = $this->createIconIdentifier($itemConfiguration[2] ?? '');
            if ($columnName === 'list_type') {
                $identifier = 'plugins_' . $contentType;
            } else {
                $identifier = ($itemConfiguration[3] ?? 'default') . '_' . $contentType;
            }
            $wizards[$identifier] = [
                'title' => $this->getLanguageService()->sL($itemConfiguration[0]),
                'iconIdentifier' => $iconIdentifier,
                'tt_content_defValues' => $newDefaultValues
            ];
        }

        return $wizards;
    }

    protected function appendPageTypes(array $wizards): array
    {
        $wizards['pagetypes']['header'] = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.pagetypes_select');
        $backendUser = $this->getBackendUser();
        $usedPageTypes = $this->getUsedPageTypes();
        foreach ($GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'] as $no => $pageTypeConfiguration) {
            if ($pageTypeConfiguration[1] === '--div--') {
                continue;
            }
            if ($backendUser->isAdmin() || $backendUser->check('pagetypes_select', $pageTypeConfiguration[1])) {
                $wizards['pagetypes_' . $no] = [
                    'title' => $this->getLanguageService()->sL($pageTypeConfiguration[0]),
                    'iconIdentifier' => $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][$pageTypeConfiguration[1]],
                    'filter' => sprintf('table=pages doktype=%d', $pageTypeConfiguration[1]),
                    'disabled' => !in_array($pageTypeConfiguration[1], $usedPageTypes)
                ];
            }
        }

        return $wizards;
    }

    protected function appendRecords(array $wizards): array
    {
        $wizards['records']['header'] = $this->getLanguageService()->sL('LLL:EXT:pagetreefilter/Resources/Private/Language/locallang.xlf:wizard_tab_records');
        $backendUser = $this->getBackendUser();
        foreach($GLOBALS['TCA'] as $tableName => $tableConfiguration) {
            if (($tableConfiguration['ctrl']['hideTable'] ?? false) === false &&
                (
                    $backendUser->isAdmin() ||
                    (
                        ($tableConfiguration['ctrl']['adminOnly'] ?? false) === false &&
                        $backendUser->check('tables_select', $tableName)
                    )
                )
            ) {
                $wizards['records_' . $tableName] = [
                    'title' => $this->getLanguageService()->sL($tableConfiguration['ctrl']['title']),
                    'iconIdentifier' => $this->iconFactory->mapRecordTypeToIconIdentifier($tableName, []),
                    'filter' => sprintf('table=%s', $tableName),
                    'disabled' => $this->areRecordsInTable($tableName) ? false : true
                ];
            }
        }

        return $wizards;
    }

    protected function getUsedPageTypes(): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $pageTypes = $queryBuilder
            ->select('doktype')
            ->from('pages')
            ->groupBy('doktype')
            ->execute()
            ->fetchFirstColumn();

        return $pageTypes;
    }

    protected function keepOnlyListTypeAndCTypeInDefaultValues(array $wizards): array
    {
        foreach($wizards as $index => $wizard) {
            if (!is_array($wizard['tt_content_defValues'] ?? false)) {
                continue;
            }
            foreach($wizard['tt_content_defValues'] as $columnName => $defaultValue) {
                if (!in_array($columnName, ['CType', 'list_type'])) {
                    unset($wizards[$index]['tt_content_defValues'][$columnName]);
                }
            }
        }

        return $wizards;
    }

    protected function disableWizardsHavingNoResults(array $wizards): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $rows = $queryBuilder
            ->select('CType', 'list_type')
            ->from('tt_content')
            ->groupBy('CType', 'list_type')
            ->execute()
            ->fetchAllAssociative();

        $generalPluginEnabled = false;
        $contentTypes = [];
        foreach ($rows as $row) {
            if ($row['CType'] !== 'list') {
                unset($row['list_type']);
                $contentTypes[$row['CType']] = $row;
            } else {
                $contentTypes[$row['CType'] . $row['list_type']] = $row;
                $generalPluginEnabled = true;
            }
        }

        foreach ($wizards as $no => $wizard) {
            if (!isset($wizard['tt_content_defValues'])) {
                continue;
            }

            if ($wizard['tt_content_defValues']['CType'] !== 'list') {
                unset($wizard['tt_content_defValues']['list_type']);
            }

            if ($wizard['tt_content_defValues'] === ['CType' => 'list']) {
                $wizard[$no]['disabled'] = $generalPluginEnabled;
            } else {
                $keyFound = array_search($wizard['tt_content_defValues'], $contentTypes);
                $wizards[$no]['disabled'] = is_bool($keyFound);
                unset($contentTypes[$keyFound]);
            }
        }

        $this->unknownContentTypes = $contentTypes;

        return $wizards;
    }

    protected function appendUnknownContentTypes(array $wizards): array
    {
        if ($this->unknownContentTypes !== [] && $this->getBackendUser()->isAdmin()) {
            $wizards['unknown']['header'] = '?';
            foreach ($this->unknownContentTypes as $no => $unknownContentType) {
                $filterParts = [];
                foreach ($unknownContentType as $fieldName => $fieldValue) {
                    $filterParts[] = sprintf('%s=%s', $fieldName, $fieldValue);
                }

                $wizards['unknown_unknown' . $no] = [
                    'title' => $unknownContentType['list_type'] ?? $unknownContentType['CType'],
                    'iconIdentifier' => 'default-not-found',
                    'filter' => sprintf('table=tt_content %s', implode(' ', $filterParts)),
                    'disabled' => false
                ];
            }
        }

        return $wizards;
    }

    protected function appendExtended($wizards)
    {
        $customWizardItems = ConfigurationUtility::getCustomWizardItems();
        if ($customWizardItems !== []) {
            $wizards['filters']['header'] =
                $this->getLanguageService()->sL('LLL:EXT:pagetreefilter/Resources/Private/Language/locallang.xlf:wizard_tab_extended_filters');

            foreach ($customWizardItems as $identifier => $wizardItem) {
                $wizards['filters_' . $identifier] = [
                    'title' => $this->getLanguageService()->sL($wizardItem['title']),
                    'description' => $this->getLanguageService()->sL($wizardItem['description'] ?? ''),
                    'iconIdentifier' => $wizardItem['iconIdentifier'] ?? 'actions-filter',
                    'filter' => $wizardItem['filter']
                ];
            }
        }

        return $wizards;
    }

    protected function areRecordsInTable($tableName): bool
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $oneRecord = $queryBuilder->select('*')
            ->from($tableName)
            ->setMaxResults(1)
            ->execute()
            ->fetchOne();

        return $oneRecord !== false;
    }

    protected function getFluidTemplateObject(string $templateName): StandaloneView
    {
        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:pagetreefilter/Resources/Private/Templates/Filter/' . $templateName . '.html'));
        $view->getRequest()->setControllerExtensionName('Pagetreefilter');

        return $view;
    }

    protected function createIconIdentifier($iconPath): string
    {
        if ($iconPath === '' || strpos($iconPath, '/') === false) {
            return $iconPath;
        }

        $iconIdentifier = 'tx-pagetreefilter-plugin-' . sha1($iconPath);
        $provider = str_ends_with(strtolower($iconPath), '.svg') ? SvgIconProvider::class : BitmapIconProvider::class;
        $this->iconRegistry->registerIcon(
            $iconIdentifier,
            $provider,
            ['source' => $iconPath]
        );

        return $iconIdentifier;
    }

    /**
     * EXT:content_defender limits placing content elements in any colPos. The hook needs to be disabled to be able to
     * see all possible wizard items.
     */
    protected function disableContentDefenderHook(): void
    {
        unset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook']['content_defender']);
    }

    public function injectIconRegistry(IconRegistry $iconRegistry)
    {
        $this->iconRegistry = $iconRegistry;
    }

    /**
     * Added for EXT:flux
     */
    public function getId()
    {
        return $this->id;
    }
}
