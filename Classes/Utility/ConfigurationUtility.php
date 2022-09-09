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

        $isEnabledForNormalUser = (bool)($backendUser->getTSConfig()['tx_pagetreefilter.']['enable'] ?? false);

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

    public static function getExtendedFilters(): array
    {
        $backendUser = self::getBackendUser();
        $filters = $backendUser->getTSConfig()['tx_pagetreefilter.']['filters.'] ?? [];
        foreach ($filters as $name => $configuration) {
            $filters[rtrim($name, '.')] = $configuration;
            unset($filters[$name]);
        }
        return $filters;
    }

    public static function getCustomWizardItems(): array
    {
        $backendUser = self::getBackendUser();
        $wizardItems = $backendUser->getTSConfig()['tx_pagetreefilter.']['wizardItems.'] ?? [];
        foreach ($wizardItems as $name => $configuration) {
            $wizardItems[rtrim($name, '.')] = $configuration;
            unset($wizardItems[$name]);
        }
        return $wizardItems;
    }

    protected static function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
