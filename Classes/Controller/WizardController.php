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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class WizardController extends NewContentElementController
{
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
        $wizards = parent::getWizards();
        $wizards = $this->disableWizardsHavingNoResults($wizards);
        $wizards = $this->appendRecords($wizards);
        $wizards = $this->appendPageTypes($wizards);

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
            if ((bool)$tableConfiguration['ctrl']['hideTable'] === false &&
                (
                    $backendUser->isAdmin() ||
                    (
                        (bool)$tableConfiguration['ctrl']['adminOnly'] === false &&
                        $backendUser->check('tables_select', $tableName)
                    )
                )
            ) {
                if (isset($tableConfiguration['ctrl']['typeicon_classes']['default'])) {
                    $iconIdentifier = $tableConfiguration['ctrl']['typeicon_classes']['default'];
                } else {
                    $iconIdentifier = sprintf('tcarecords-%s-default', $tableName);
                }

                $wizards['records_' . $tableName] = [
                    'title' => $this->getLanguageService()->sL($tableConfiguration['ctrl']['title']),
                    'iconIdentifier' => $iconIdentifier,
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

    protected function disableWizardsHavingNoResults(array $wizards): array
    {
        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
        $contentTypes = $queryBuilder
            ->select('CType', 'list_type')
            ->from('tt_content')
            ->groupBy('CType', 'list_type')
            ->execute()
            ->fetchAllAssociative();

        $generalPluginEnabled = false;
        foreach ($contentTypes as $no => $contentTypeCombination) {
            if ($contentTypeCombination['CType'] !== 'list') {
                unset($contentTypes[$no]['list_type']);
            } else {
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
                $wizards[$no]['disabled'] = !in_array($wizard['tt_content_defValues'], $contentTypes);
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
}