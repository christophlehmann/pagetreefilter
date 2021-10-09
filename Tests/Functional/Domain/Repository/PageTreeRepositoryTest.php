<?php

namespace Lemming\PageTreeFilter\Tests\Functional\Domain\Repository;

use Lemming\PageTreeFilter\Domain\Repository\PageTreeRepository;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class PageTreeRepositoryTest extends FunctionalTestCase
{
    /**
     * @var PageTreeRepository
     */
    protected $subject;


    protected $testExtensionsToLoad = ['typo3conf/ext/pagetreefilter'];

    public function setUp(): void
    {
        parent::setUp();
        $this->setUpBackendUserFromFixture(1);
        $this->importDataSet(__DIR__ . '/../../Fixtures/pages.xml');

        $this->subject = new PageTreeRepository();
    }

    /**
     * @test
     */
    public function wildcardFilterAtBegin()
    {
        $tree = $this->subject->fetchFilteredTree('table=tt_content header=*element', [0], '');
        $this->assertCount(1, $tree['_children']);
        $this->assertSame($tree['_children'][0]['uid'], 2);
    }

    /**
     * @test
     */
    public function wildcardFilterAtEnd()
    {
        $tree = $this->subject->fetchFilteredTree('table=tt_content header=a*', [0], '');
        $this->assertCount(1, $tree['_children']);
        $this->assertSame($tree['_children'][0]['uid'], 2);
    }

    /**
     * @test
     */
    public function wildcardFilterAtBeginAndEnd()
    {
        $tree = $this->subject->fetchFilteredTree('table=tt_content header=*content*', [0], '');
        $this->assertCount(1, $tree['_children']);
        $this->assertSame($tree['_children'][0]['uid'], 2);
    }

    /**
     * @test
     */
    public function exactMatchFilter()
    {
        $tree = $this->subject->fetchFilteredTree('table=pages title=Homepage', [0], '');
        $this->assertCount(1, $tree['_children']);
        $this->assertSame($tree['_children'][0]['uid'], 1);
    }
}