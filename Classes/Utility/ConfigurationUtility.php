<?php
declare(strict_types=1);

namespace Lemming\PageTreeFilter\Utility;

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class ConfigurationUtility
{
    public static function isWizardEnabled(): bool
    {
        $backendUser = self::getBackendUser();
        if (!$backendUser) {
            return false;
        }
        if ($backendUser->isAdmin()) {
            return true;
        }

        $isEnabledForNormalUser = $backendUser->getTSConfig()['tx_pagetreefilter.']['enable'] ?? 0 == 1;

        return $isEnabledForNormalUser;
    }

    public static function getPageId()
    {
        $backendUser = self::getBackendUser();
        if (!$backendUser->isAdmin()) {
            $webMounts = $backendUser->returnWebmounts();
            foreach ($webMounts as $pageId) {
                if ($pageId != 0) {
                    return $pageId;
                }
            }
        }

        return GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('pagetreefilter', 'pageId');
    }

    protected static function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}