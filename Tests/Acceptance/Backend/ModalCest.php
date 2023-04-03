<?php

declare(strict_types=1);
namespace Lemming\PageTreeFilter\Tests\Acceptance\Backend;

use Lemming\PageTreeFilter\Tests\Acceptance\Support\BackendTester;

/**
 * Tests highlighting in page tree
 */
class ModalCest
{
    protected static $buttonSelector = 'button#pagetreefilter';
    protected static $modalSelector = '.pagetreefilter-wizard';
    protected static $pageTreeSelector = '#typo3-pagetree-tree';
    protected static $showUnusedButtonSelector = 'button[name="pagetreefilter-wizard-show-unused"]';

    protected BackendTester $tester;

    /**
     * It's executed before every test
     */
    public function _before(BackendTester $I)
    {
        $this->tester = $I;
        $I->useExistingSession('admin');
    }

    public function buttonIsVisbleAbovePageTree(BackendTester $I)
    {
        $I->waitForElement(self::$buttonSelector);
        $I->makeScreenshot('buttonIsVisbleAbovePageTree');
    }

    /**
     * @depends buttonIsVisbleAbovePageTree
     */
    public function toggleUnusedElementsWork(BackendTester $I)
    {
        $this->openModal();

        $I->click('Page types');
        $I->makeElementScreenshot(self::$modalSelector, 'toggleUnusedElementsWork_unusedElementsAreHidden');
        $I->see('Standard', self::$modalSelector);
        $I->dontSee('Shortcut', self::$modalSelector);

        $I->click(self::$showUnusedButtonSelector);
        $I->click('Page types');
        $I->makeElementScreenshot(self::$modalSelector, 'toggleUnusedElementsWork_unusedElementsAreVisible');
        $I->See('Shortcut', self::$modalSelector);
    }

    /**
     * @depends buttonIsVisbleAbovePageTree
     */
    public function highlightPagesWithDoktype1(BackendTester $I)
    {
        $this->openModal();
        $I->click('Page types');
        $I->click('Standard');
        $I->waitForElement(self::$pageTreeSelector . ' rect.pagetreefilter-highlighted');
        $I->see('Startseite', self::$pageTreeSelector);
        $I->makeScreenshot('highlightPagesWithDoktype1_startseiteIsHighlightedNonElse');
        $I->dontSee('Datensätze', self::$pageTreeSelector);
    }

    protected function openModal()
    {
        $I = $this->tester;
        $I->click(self::$buttonSelector);
        $I->waitForText('Page types', 5, self::$modalSelector);
    }
}
