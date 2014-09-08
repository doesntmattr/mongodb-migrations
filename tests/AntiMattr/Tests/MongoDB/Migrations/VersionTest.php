<?php

namespace AntiMattr\Tests\MongoDB\Migrations;

use AntiMattr\MongoDB\Migrations\AbstractMigration;
use AntiMattr\MongoDB\Migrations\Version;
use AntiMattr\TestCase\AntiMattrTestCase;
use Doctrine\MongoDB\Database;

class VersionTest extends AntiMattrTestCase
{
    private $className;
    private $configuration;
    private $connections;
    private $db;
    private $migration;
    private $version;
    private $versionName;
    private $outputWriter;
    private $statistics;

    protected function setUp()
    {
        $this->className = 'AntiMattr\Tests\MongoDB\Migrations\Version20140908000000';
        $this->configuration = $this->buildMock('AntiMattr\MongoDB\Migrations\Configuration\Configuration');
        $this->connection = $this->buildMock('Doctrine\MongoDB\Connection');
        $this->db = $this->buildMock('Doctrine\MongoDB\Database');
        $this->migration = $this->buildMock('AntiMattr\Tests\MongoDB\Migrations\Version20140908000000');
        $this->outputWriter = $this->buildMock('AntiMattr\MongoDB\Migrations\OutputWriter');
        $this->statistics = $this->buildMock('AntiMattr\MongoDB\Migrations\Collection\Statistics');
        $this->versionName = '20140908000000';

        $this->configuration->expects($this->once())
            ->method('getOutputWriter')
            ->will($this->returnValue($this->outputWriter));
        $this->configuration->expects($this->once())
            ->method('getConnection')
            ->will($this->returnValue($this->connection));
        $this->configuration->expects($this->once())
            ->method('getDatabase')
            ->will($this->returnValue($this->db));

        $this->version = new VersionStub($this->configuration, $this->versionName, $this->className);
        $this->version->setStatistics($this->statistics);
        $this->version->setMigration($this->migration);
    }

    public function testConstructor()
    {
        $this->assertEquals($this->configuration, $this->version->getConfiguration());
        $this->assertEquals(Version::STATE_NONE, $this->version->getState());
        $this->assertEquals($this->versionName, $this->version->getVersion());
        $this->assertEquals($this->versionName, (string) $this->version);
        $this->assertNotNull($this->version->getMigration());
    }

    public function testAnalyzeThrowsMongoException()
    {
        $collection = $this->buildMock('Doctrine\MongoDB\Collection');
        $this->statistics->expects($this->once())
            ->method('setCollection')
            ->with($collection);

        $collection->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('test_name'));

        $expectedException = new \MongoException();

        $this->statistics->expects($this->once())
            ->method('updateBefore')
            ->will($this->throwException($expectedException));

        $this->outputWriter->expects($this->once())
            ->method('write');

        $this->version->analyze($collection);
    }

    public function testAnalyze()
    {
        $collection = $this->buildMock('Doctrine\MongoDB\Collection');
        $this->statistics->expects($this->once())
            ->method('setCollection')
            ->with($collection);

        $collection->expects($this->once())
            ->method('getName')
            ->will($this->returnValue('test_name'));

        $this->statistics->expects($this->once())
            ->method('updateBefore');

        $this->outputWriter->expects($this->never())
            ->method('write');

        $this->version->analyze($collection);
    }

    public function testMarkMigrated()
    {
        $timestamp = $this->buildMock('MongoTimestamp');
        $this->version->setTimestamp($timestamp);

        $collection = $this->buildMock('Doctrine\MongoDB\Collection');
        $this->configuration->expects($this->once())
            ->method('createMigrationCollection');

        $this->configuration->expects($this->once())
            ->method('getCollection')
            ->will($this->returnValue($collection));

        $insert = array(
            'v' => $this->versionName,
            't' => $timestamp
        );

        $collection->expects($this->once())
            ->method('insert')
            ->with($insert);

        $this->version->markMigrated();
    }

    public function testMarkNotMigrated()
    {
        $timestamp = $this->buildMock('MongoTimestamp');
        $this->version->setTimestamp($timestamp);

        $collection = $this->buildMock('Doctrine\MongoDB\Collection');
        $this->configuration->expects($this->once())
            ->method('createMigrationCollection');

        $this->configuration->expects($this->once())
            ->method('getCollection')
            ->will($this->returnValue($collection));

        $remove = array(
            'v' => $this->versionName
        );

        $collection->expects($this->once())
            ->method('remove')
            ->with($remove);

        $this->version->markNotMigrated();
    }

    public function testUpdateStatisticsAfterThrowsMongoException()
    {
        $collection = $this->buildMock('Doctrine\MongoDB\Collection');
        $this->statistics->expects($this->once())
            ->method('setCollection')
            ->with($collection);

        $expectedException = new \MongoException();

        $this->statistics->expects($this->once())
            ->method('updateAfter')
            ->will($this->throwException($expectedException));

        $this->outputWriter->expects($this->once())
            ->method('write');

        $this->version->analyze($collection);
        $this->version->doUpdateStatisticsAfter();
    }

    public function testUpdateStatisticsAfter()
    {
        $collection = $this->buildMock('Doctrine\MongoDB\Collection');
        $this->statistics->expects($this->once())
            ->method('setCollection')
            ->with($collection);

        $this->statistics->expects($this->once())
            ->method('getCollection')
            ->will($this->returnValue($collection));

        $collection->expects($this->exactly(2))
            ->method('getName')
            ->will($this->returnValue('test_name'));

        $this->statistics->expects($this->once())
            ->method('updateAfter');

        $this->outputWriter->expects($this->never())
            ->method('write');

        $this->version->analyze($collection);
        $this->version->doUpdateStatisticsAfter();
    }

    public function testIsMigrated()
    {
        $this->configuration->expects($this->once())
            ->method('hasVersionMigrated')
            ->with($this->version);

        $this->version->isMigrated();
    }

    /**
     * @dataProvider provideDirection
     */
    public function testExecuteThrowsSkipException($direction)
    {
        $expectedException = $this->buildMock('AntiMattr\MongoDB\Migrations\Exception\SkipException');

        $this->migration->expects($this->once())
            ->method($direction)
            ->will($this->throwException($expectedException));

        $collection = $this->buildMock('Doctrine\MongoDB\Collection');
        $this->configuration->expects($this->once())
            ->method('createMigrationCollection');

        $this->configuration->expects($this->once())
            ->method('getCollection')
            ->will($this->returnValue($collection));

        $this->version->execute($direction);
    }

    /**
     * @dataProvider provideDirection
     */
    public function testExecute($direction)
    {
        $this->migration->expects($this->once())
            ->method('pre'.$direction);

        $this->migration->expects($this->once())
            ->method($direction);

        $this->migration->expects($this->once())
            ->method('post'.$direction);

        $collection = $this->buildMock('Doctrine\MongoDB\Collection');
        $this->configuration->expects($this->once())
            ->method('createMigrationCollection');

        $this->configuration->expects($this->once())
            ->method('getCollection')
            ->will($this->returnValue($collection));

        $this->version->execute($direction);
    }

    public function provideDirection()
    {
        return array(
            array('up'),
            array('down')
        );
    }
}

class VersionStub extends Version
{
    private $statistics;
    private $timestamp;

    public function doUpdateStatisticsAfter()
    {
        $this->updateStatisticsAfter();
    }

    public function getState()
    {
        return $this->state;
    }

    public function setMigration($migration)
    {
        $this->migration = $migration;
    }

    public function setStatistics($statistics)
    {
        $this->statistics = $statistics;
    }

    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }

    protected function createStatistics()
    {
        return $this->statistics;
    }

    protected function createMigration()
    {
        return $this->migration;
    }

    protected function createMongoTimestamp()
    {
        return $this->timestamp;
    }
}

class Version20140908000000 extends AbstractMigration
{
    public function getDescription()
    {
        return 'Test Version';
    }

    public function up(Database $db)
    {

    }

    public function down(Database $db)
    {

    }
}
