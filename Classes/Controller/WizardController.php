<?php
declare(strict_types=1);

namespace Lemming\PageTreeFilter\Controller;

use Lemming\PageTreeFilter\Utility\ConfigurationUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController;
use TYPO3\CMS\Backend\Controller\Event\ModifyNewContentElementWizardItemsEvent;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Imaging\IconProvider\BitmapIconProvider;
use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class WizardController extends NewContentElementController
{
    protected IconRegistry $iconRegistry;

    protected IconFactory $iconFactory;

    protected array $unknownContentTypes = [];

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

    public function wizardAction(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->id || $this->pageInfo === []) {
            return new HtmlResponse($this->getLanguageService()->sL('LLL:EXT:pagetreefilter/Resources/Private/Language/locallang.xlf:wizard_error_message'));
        }

        // Get processed and modified wizard items
        $wizardItems = $this->eventDispatcher->dispatch(
            new ModifyNewContentElementWizardItemsEvent(
                parent::getWizards(),
                $this->pageInfo,
                $this->colPos,
                $this->sys_language,
                $this->uid_pid,
            )
        )->getWizardItems();

        $wizardItems = $this->appendItemsHavingNoWizardConfiguration($wizardItems, 'list_type');
        $wizardItems = $this->appendItemsHavingNoWizardConfiguration($wizardItems, 'CType');
        ksort($wizardItems);
        $wizardItems = $this->keepOnlyListTypeAndCTypeInDefaultValues($wizardItems);
        $wizardItems = $this->disableWizardsHavingNoResults($wizardItems);
        $wizardItems = $this->appendRecords($wizardItems);
        $wizardItems = $this->appendPageTypes($wizardItems);
        $wizardItems = $this->appendUnknownContentTypes($wizardItems);

        $key = 0;
        $menuItems = [];
        foreach ($wizardItems as $wizardKey => $wizardItem) {
            // An item is either a header or an item rendered with title/description and icon:
            if (isset($wizardItem['header'])) {
                $menuItems[] = [
                    'label' => $wizardItem['header'] ?: '-',
                    'contentItems' => [],
                ];
                $key = count($menuItems) - 1;
            } else {
                // Initialize the view variables for the item
                $viewVariables = [
                    'wizardInformation' => $wizardItem,
                    'wizardKey' => $wizardKey,
                    'icon' => $this->iconFactory->getIcon(($wizardItem['iconIdentifier'] ?? ''), overlayIdentifier:($wizardItem['iconOverlay'] ?? ''))->render(),
                ];
                $menuItems[$key]['contentItems'][] = $viewVariables;
            }
        }

        $view = $this->backendViewFactory->create($request);
        $view->assignMultiple([
            'tabsMenuItems' => $menuItems,
            'tabsMenuId' => 'DTM-a31afc8fb616dc290e6626a9f3c9c433', // Just a unique id starting with DTM-
        ]);

        return new HtmlResponse($view->render('Wizard'));
    }

    protected function appendItemsHavingNoWizardConfiguration($wizardItems, $columnName): array
    {
        foreach ($GLOBALS['TCA']['tt_content']['columns'][$columnName]['config']['items'] ?? [] as $itemConfiguration) {
            $contentType = $itemConfiguration['value'];

            if (empty($contentType) || $contentType === '--div--') {
                continue;
            }

            if (!$this->getBackendUser()->checkAuthMode('tt_content', $columnName, $contentType)) {
                continue;
            }

            $newDefaultValues = $columnName === 'list_type' ? ['CType' => 'list', 'list_type' => $contentType] : ['CType' => $contentType];
            $availableDefaultValues = array_map(function ($wizard) {
                return $wizard['tt_content_defValues'] ?? [];
            }, $wizardItems);
            if (in_array($newDefaultValues, $availableDefaultValues)) {
                continue;
            }

            $iconIdentifier = $this->createIconIdentifier($itemConfiguration['icon'] ?? '');
            if ($columnName === 'list_type') {
                $identifier = 'plugins_' . $contentType;
            } else {
                $identifier = ($itemConfiguration['group'] ?? 'default') . '_' . $contentType;
            }
            $wizardItems[$identifier] = [
                'title' => $this->getLanguageService()->sL($itemConfiguration['label']),
                'iconIdentifier' => $iconIdentifier,
                'tt_content_defValues' => $newDefaultValues
            ];
        }

        return $wizardItems;
    }

    protected function appendPageTypes(array $wizardItems): array
    {
        $wizardItems['pagetypes']['header'] = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:be_groups.pagetypes_select');
        $backendUser = $this->getBackendUser();
        $usedPageTypes = $this->getUsedPageTypes();
        foreach ($GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'] as $no => $pageTypeConfiguration) {
            if ($pageTypeConfiguration['value'] === '--div--') {
                continue;
            }
            if ($backendUser->isAdmin() || $backendUser->check('pagetypes_select', $pageTypeConfiguration['value'])) {
                $wizardItems['pagetypes_' . $no] = [
                    'title' => $this->getLanguageService()->sL($pageTypeConfiguration['label']),
                    'iconIdentifier' => $GLOBALS['TCA']['pages']['ctrl']['typeicon_classes'][$pageTypeConfiguration['value']],
                    'filter' => sprintf('table=pages doktype=%d', $pageTypeConfiguration['value']),
                    'disabled' => !in_array($pageTypeConfiguration['value'], $usedPageTypes)
                ];
            }
        }

        return $wizardItems;
    }

    protected function appendRecords(array $wizardItems): array
    {
        $wizardItems['records']['header'] = $this->getLanguageService()->sL('LLL:EXT:pagetreefilter/Resources/Private/Language/locallang.xlf:wizard_tab_records');
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
                $wizardItems['records_' . $tableName] = [
                    'title' => $this->getLanguageService()->sL($tableConfiguration['ctrl']['title']),
                    'iconIdentifier' => $this->iconFactory->mapRecordTypeToIconIdentifier($tableName, []),
                    'filter' => sprintf('table=%s', $tableName),
                    'disabled' => $this->areRecordsInTable($tableName) ? false : true
                ];
            }
        }

        return $wizardItems;
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
            ->executeQuery()
            ->fetchFirstColumn();

        return $pageTypes;
    }

    protected function keepOnlyListTypeAndCTypeInDefaultValues(array $wizardItems): array
    {
        foreach($wizardItems as $index => $wizard) {
            if (!is_array($wizard['tt_content_defValues'] ?? false)) {
                continue;
            }
            foreach($wizard['tt_content_defValues'] as $columnName => $defaultValue) {
                if (!in_array($columnName, ['CType', 'list_type'])) {
                    unset($wizardItems[$index]['tt_content_defValues'][$columnName]);
                }
            }
        }

        return $wizardItems;
    }

    protected function disableWizardsHavingNoResults(array $wizardItems): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $rows = $queryBuilder
            ->select('CType', 'list_type')
            ->from('tt_content')
            ->groupBy('CType', 'list_type')
            ->executeQuery()
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

        foreach ($wizardItems as $no => $wizard) {
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
                $wizardItems[$no]['disabled'] = is_bool($keyFound);
                unset($contentTypes[$keyFound]);
            }
        }

        $this->unknownContentTypes = $contentTypes;

        return $wizardItems;
    }

    protected function appendUnknownContentTypes(array $wizardItems): array
    {
        if ($this->unknownContentTypes !== [] && $this->getBackendUser()->isAdmin()) {
            $wizardItems['unknown']['header'] = 'unknown';
            foreach ($this->unknownContentTypes as $no => $unknownContentType) {
                $filterParts = [];
                foreach ($unknownContentType as $fieldName => $fieldValue) {
                    $filterParts[] = sprintf('%s=%s', $fieldName, $fieldValue);
                }

                $wizardItems['unknown_unknown' . $no] = [
                    'title' => $unknownContentType['list_type'] ?? $unknownContentType['CType'],
                    'iconIdentifier' => 'default-not-found',
                    'filter' => sprintf('table=tt_content %s', implode(' ', $filterParts)),
                    'disabled' => false
                ];
            }
        }

        return $wizardItems;
    }

    protected function areRecordsInTable($tableName): bool
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($tableName);
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $oneRecord = $queryBuilder->select('*')
            ->from($tableName)
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchOne();

        return $oneRecord !== false;
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

    public function injectIconRegistry(IconRegistry $iconRegistry)
    {
        $this->iconRegistry = $iconRegistry;
    }

    public function injectIconFactory(IconFactory $iconFactory)
    {
        $this->iconFactory = $iconFactory;
    }

    /**
     * Added for EXT:flux
     */
    public function getId()
    {
        return $this->id;
    }
}
