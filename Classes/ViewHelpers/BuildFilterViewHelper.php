<?php

namespace Lemming\PageTreeFilter\ViewHelpers;

use Lemming\PageTreeFilter\Utility\ConfigurationUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

class BuildFilterViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    public function initializeArguments()
    {
        parent::initializeArguments();
        $this->registerArgument('wizardInformation', 'array', '', true);
    }

    public static function renderStatic(
        array $arguments,
        \Closure $renderChildrenClosure,
        RenderingContextInterface $renderingContext
    ) {
        $wizardInformation = $arguments['wizardInformation'];
        if (isset($wizardInformation['filter'])) {
            return $wizardInformation['filter'];
        }

        $filter = 'table=tt_content';
        if (isset($wizardInformation['tt_content_defValues'])) {
            foreach($wizardInformation['tt_content_defValues'] as $field => $value) {
                if (in_array($field, ['CType', 'list_type', 'tx_gridelements_backend_layout'])) {
                    $filter = sprintf('%s %s=%s', $filter, $field, $value);
                }
            }
        }
        $filter = htmlspecialchars($filter, ENT_QUOTES);

        return $filter;
    }
}
