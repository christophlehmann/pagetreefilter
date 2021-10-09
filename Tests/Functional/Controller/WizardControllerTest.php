<?php

namespace Lemming\PageTreeFilter\Tests\Functional\Controller;

use Lemming\PageTreeFilter\Controller\WizardController;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class WizardControllerTest extends FunctionalTestCase
{
    /**
     * @var WizardController
     */
    protected $subject;

    protected $testExtensionsToLoad = ['typo3conf/ext/pagetreefilter'];

    public function setUp(): void
    {
        parent::setUp();

        $this->setUpBackendUserFromFixture(1);
        $this->importDataSet(__DIR__ . '/../Fixtures/pages.xml');
        Bootstrap::initializeLanguageObject();

        $this->subject = GeneralUtility::makeInstance(WizardController::class);
    }

    /**
     * @test
     */
    public function wizardItemsWithResultsAreEnabled()
    {
        $wizardItems = $this->subject->getWizards();

        $this->assertArrayHasKey('common_header', $wizardItems);
        $this->assertFalse($wizardItems['common_header']['disabled']);

        $this->assertArrayHasKey('records_tt_content', $wizardItems);
        $this->assertFalse($wizardItems['records_tt_content']['disabled']);

        $this->assertArrayHasKey('pagetypes_1', $wizardItems);
        $this->assertFalse($wizardItems['pagetypes_1']['disabled']);
    }

    /**
     * @test
     */
    public function wizardItemsWithoutResultsAreDisabled()
    {
        $wizardItems = $this->subject->getWizards();

        $this->assertArrayHasKey('records_sys_category', $wizardItems);
        $this->assertTrue($wizardItems['records_sys_category']['disabled']);

        $this->assertArrayHasKey('pagetypes_4', $wizardItems);
        $this->assertTrue($wizardItems['pagetypes_4']['disabled']);
    }

}