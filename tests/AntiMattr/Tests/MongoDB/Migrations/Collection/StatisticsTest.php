<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Collection;

use AntiMattr\MongoDB\Migrations\Collection\Statistics;
use AntiMattr\TestCase\AntiMattrTestCase;

class StatisticsTest extends AntiMattrTestCase
{
    private $collection;
    private $statistics;

    protected function setUp()
    {
        $this->collection = $this->buildMock('Doctrine\MongoDB\Collection');
        $this->statistics = new Statistics();
    }

    public function testSetGetCollection()
    {
        $this->statistics->setCollection($this->collection);
        $this->assertEquals($this->collection, $this->statistics->getCollection());
    }

    /**
     * @expectedException Exception
     */
    public function testGetCollectionStatsThrowsExceptionWhenDataNotFound()
    {
        $this->statistics = new StatisticsStub();
        $this->statistics->setCollection($this->collection);

        $database = $this->buildMock('Doctrine\MongoDB\Database');

        $this->collection->expects($this->once())
            ->method('getDatabase')
            ->will($this->returnValue($database));

        $this->collection->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('example'));

        $this->statistics->doGetCollectionStats();
    }

    /**
     * @expectedException Exception
     */
    public function testGetCollectionStatsThrowsExceptionWhenErrmsgFound()
    {
        $this->statistics = new StatisticsStub();
        $this->statistics->setCollection($this->collection);

        $database = $this->buildMock('Doctrine\MongoDB\Database');

        $this->collection->expects($this->once())
            ->method('getDatabase')
            ->will($this->returnValue($database));

        $this->collection->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('example'));

        $data = array(
            'errmsg' => 'foo'
        );

        $database->expects($this->once())
            ->method('command')
            ->will($this->returnValue($data));

        $this->statistics->doGetCollectionStats();
    }

    public function testGetCollectionStats()
    {
        $this->statistics = new StatisticsStub();
        $this->statistics->setCollection($this->collection);

        $database = $this->buildMock('Doctrine\MongoDB\Database');

        $this->collection->expects($this->once())
            ->method('getDatabase')
            ->will($this->returnValue($database));

        $this->collection->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('example'));

        $expectedData = array(
            'count' => 100
        );

        $database->expects($this->once())
            ->method('command')
            ->will($this->returnValue($expectedData));

        $data = $this->statistics->doGetCollectionStats();

        $this->assertSame($expectedData, $data);
    }
}

class StatisticsStub extends Statistics
{
    public function doGetCollectionStats()
    {
        return $this->getCollectionStats();
    }
}
