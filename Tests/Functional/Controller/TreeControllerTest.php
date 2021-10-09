<?php

namespace Lemming\PageTreeFilter\Tests\Functional\Controller;

use Lemming\PageTreeFilter\Controller\TreeController;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class TreeControllerTest extends FunctionalTestCase
{
    /**
     * @var TreeController
     */
    protected $subject;

    /**
     * @var ServerRequestInterface
     */
    protected $request;

    protected $testExtensionsToLoad = ['typo3conf/ext/pagetreefilter'];

    public function setUp(): void
    {
        parent::setUp();
        $this->setUpBackendUserFromFixture(1);
        $this->importDataSet(__DIR__ . '/../Fixtures/pages.xml');

        GeneralUtility::setIndpEnv('TYPO3_REQUEST_URL', 'http://localhost/typo3/index.php');
        $this->request = ServerRequestFactory::fromGlobals();

        $this->subject = new TreeController();
    }

    /**
     * @test
     */
    public function filterForTablePagesReturnsHighlightedPage()
    {
        $request = $this->request->withQueryParams(['q' => 'table=pages']);
        $jsonResponse = $this->subject->filterDataAction($request);
        $pages = json_decode($jsonResponse->getBody()->getContents(), true);

        $this->assertSame('pagetreefilter-highlighted', $pages[1]['class']);
        $this->assertSame('pagetreefilter-highlighted', $pages[2]['class']);
    }

    /**
     * @test
     */
    public function wrongFilterReturnsOnlyRootpageWithErrorMessage()
    {
        Bootstrap::initializeLanguageObject();
        $request = $this->request->withQueryParams(['q' => 'table=nonexistenttable']);
        $jsonResponse = $this->subject->filterDataAction($request);
        $pages = json_decode($jsonResponse->getBody()->getContents(), true);

        $this->assertCount(1, $pages);
        $this->assertStringContainsString('❌', $pages[0]['name']);
    }
}