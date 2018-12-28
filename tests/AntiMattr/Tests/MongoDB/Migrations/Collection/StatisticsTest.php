<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Collection;

use AntiMattr\MongoDB\Migrations\Collection\Statistics;
use PHPUnit\Framework\TestCase;

class StatisticsTest extends TestCase
{
    private $collection;
    private $statistics;

    protected function setUp()
    {
        $this->collection = $this->createMock('MongoDB\Collection');
        $this->statistics = new Statistics();
    }

    public function testSetGetCollection()
    {
        $this->statistics->setCollection($this->collection);
        $this->assertEquals($this->collection, $this->statistics->getCollection());
    }

    /**
     * @expectedException \Exception
     */
    public function testGetCollectionStatsThrowsExceptionWhenDataNotFound()
    {
        $database = $this->createMock('MongoDB\Database');

        $this->statistics = new StatisticsStub();
        $this->statistics->setCollection($this->collection);
        $this->statistics->setDatabase($database);

        $this->collection->expects($this->once())
            ->method('getCollectionName')
            ->will($this->returnValue('example'));

        $this->statistics->doGetCollectionStats();
    }

    /**
     * @expectedException \Exception
     */
    public function testGetCollectionStatsThrowsExceptionWhenErrmsgFound()
    {
        $database = $this->createMock('MongoDB\Database');

        $this->statistics = new StatisticsStub();
        $this->statistics->setCollection($this->collection);
        $this->statistics->setDatabase($database);

        $this->collection->expects($this->once())
            ->method('getCollectionName')
            ->will($this->returnValue('example'));

        $data = [
            'errmsg' => 'foo',
        ];

        $database->expects($this->once())
            ->method('command')
            ->will($this->returnValue($data));

        // Can't test it this way as it will return a MongoDB\Driver\Cursor which does not have a errmsg array
        // @todo what do to do here, remove this test? 
        //$this->statistics->doGetCollectionStats();
        $this->markTestIncomplete('This method needs to act on a MongoDB\Driver\Cursor object which has no error method');
    }

    public function testGetCollectionStats()
    {
        $database = $this->createMock('MongoDB\Database');

        $this->statistics = new StatisticsStub();
        $this->statistics->setCollection($this->collection);
        $this->statistics->setDatabase($database);

        $this->collection->expects($this->once())
            ->method('getCollectionName')
            ->will($this->returnValue('example'));

        $expectedData = [
            'count' => 100,
        ];

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
