<?php
declare(strict_types=1);

namespace Lemming\PageTreeFilter\Controller;

use Lemming\PageTreeFilter\Utility\ConfigurationUtility;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

class NewContentElementController extends \TYPO3\CMS\Backend\Controller\ContentElement\NewContentElementController
{
    protected function init(ServerRequestInterface $request)
    {
        $queryParameters = $request->getQueryParams();
        $queryParameters['id'] = ConfigurationUtility::getPageId();
        $requestWithPageId = $request->withQueryParams($queryParameters);
        parent::init($requestWithPageId);

        $this->menuItemView->assignMultiple([
            'isVersion10' => version_compare(VersionNumberUtility::getNumericTypo3Version(), '11', '<')
        ]);
    }

    public function getWizards(): array
    {
        $wizards = parent::getWizards();
        $wizards = $this->appendRecords($wizards);

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
                    'params' => '', // if missing it leads to an exception
                    'filter' => sprintf('table=%s', $tableName)
                ];
            }
        }

        return $wizards;
    }

    protected function getFluidTemplateObject(string $filename = 'Main.html'): StandaloneView
    {
        /** @var StandaloneView $view */
        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplatePathAndFilename(GeneralUtility::getFileAbsFileName('EXT:pagetreefilter/Resources/Private/Templates/Filter/' . $filename));
        $view->getRequest()->setControllerExtensionName('Pagetreefilter');
        return $view;
    }
}