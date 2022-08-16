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

    /**
     * Function to check language used in the search for the page tree and return into array
     *
     * @return array|int[]
     */
    public static function usedLanguagesInPageTree(): array
    {
        $backendUser = self::getBackendUser()->getTSConfig();
        $langUidArray = [0];

        if (!$backendUser) {
            return $langUidArray;
        }

        if(!is_null($backendUser['tx_pagetreefilter.']['used_language_uid'])){
            $langUidArray = array_map('intval', explode(',',$backendUser['tx_pagetreefilter.']['used_language_uid']));
        }

        return array_unique($langUidArray);
    }

    /**
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication|null
     */
    protected static function getBackendUser(): ?BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
