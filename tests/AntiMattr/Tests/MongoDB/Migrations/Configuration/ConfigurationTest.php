<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Configuration;

use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use AntiMattr\TestCase\AntiMattrTestCase;

class ConfigurationTest extends AntiMattrTestCase
{
    private $configuration;
    private $connection;

    protected function setUp()
    {
        $this->connection = $this->buildMock('Doctrine\MongoDB\Connection');
        $this->configuration = new Configuration($this->connection);
    }

    public function testConstructor()
    {
        $this->assertEquals($this->connection, $this->configuration->getConnection());
        $this->assertEmpty($this->configuration->getMigrations());
        $this->assertEmpty($this->configuration->getAvailableVersions());
    }

    public function testGetCollection()
    {
        $this->configuration->setMigrationsDatabaseName('test_antimattr_migrations');
        $this->configuration->setMigrationsCollectionName('antimattr_migration_versions_test');

        $expectedCollection = $this->buildMock('Doctrine\MongoDB\Collection');
        $database = $this->buildMock('Doctrine\MongoDB\Database');

        $this->connection->expects($this->once())
            ->method('selectDatabase')
            ->with('test_antimattr_migrations')
            ->will($this->returnValue($database));

        $database->expects($this->once())
            ->method('selectCollection')
            ->with('antimattr_migration_versions_test')
            ->will($this->returnValue($expectedCollection));

        $collection = $this->configuration->getCollection();
        $this->assertEquals($expectedCollection, $collection);
    }

    public function testGetCurrentVersion()
    {
        $this->prepareValidConfiguration();

        $directory = dirname(__DIR__)."/Resources/Migrations/";
        $this->configuration->registerMigrationsFromDirectory($directory);

        $collection = $this->buildMock('Doctrine\MongoDB\Collection');
        $database = $this->buildMock('Doctrine\MongoDB\Database');

        $this->connection->expects($this->once())
            ->method('selectDatabase')
            ->with('test_antimattr_migrations')
            ->will($this->returnValue($database));

        $database->expects($this->once())
            ->method('selectCollection')
            ->with('antimattr_migration_versions_test')
            ->will($this->returnValue($collection));

        $cursor = $this->buildMock('MongoCursor');

        $in = array('v' => array('$in' => array('20140822185742', '20140822185743', '20140822185744')));

        $collection->expects($this->once())
            ->method('find')
            ->with($in)
            ->will($this->returnValue($cursor));

        $cursor->expects($this->once())
            ->method('sort')
            ->with(array('v' => -1))
            ->will($this->returnValue($cursor));

        $cursor->expects($this->once())
            ->method('limit')
            ->with(1)
            ->will($this->returnValue($cursor));

        $cursor->expects($this->once())
            ->method('getNext')
            ->will($this->returnValue(array('v' => '20140822185743')));

        $version = $this->configuration->getCurrentVersion();

        $this->assertEquals($version, '20140822185743');
    }

    public function testGetDatabase()
    {
        $this->configuration->setMigrationsDatabaseName('test_antimattr_migrations');

        $expectedDatabase = $this->buildMock('Doctrine\MongoDB\Database');

        $this->connection->expects($this->once())
            ->method('selectDatabase')
            ->with('test_antimattr_migrations')
            ->will($this->returnValue($expectedDatabase));

        $database = $this->configuration->getDatabase();
        $this->assertEquals($expectedDatabase, $database);
    }

    public function testGetMigratedVersions()
    {
        $this->prepareValidConfiguration();

        $collection = $this->buildMock('Doctrine\MongoDB\Collection');
        $database = $this->buildMock('Doctrine\MongoDB\Database');

        $this->connection->expects($this->once())
            ->method('selectDatabase')
            ->with('test_antimattr_migrations')
            ->will($this->returnValue($database));

        $database->expects($this->once())
            ->method('selectCollection')
            ->with('antimattr_migration_versions_test')
            ->will($this->returnValue($collection));

        $foundVersions = array(
            array('v' => 'found1'),
            array('v' => 'found2')
        );

        $expectedVersions = array(
            'found1',
            'found2'
        );

        $collection->expects($this->once())
            ->method('find')
            ->will($this->returnValue($foundVersions));

        $versions = $this->configuration->getMigratedVersions();
        $this->assertEquals($expectedVersions, $versions);
    }

    public function testGetNumberOfExecutedMigrations()
    {
        $this->prepareValidConfiguration();

        $collection = $this->buildMock('Doctrine\MongoDB\Collection');
        $database = $this->buildMock('Doctrine\MongoDB\Database');

        $this->connection->expects($this->once())
            ->method('selectDatabase')
            ->with('test_antimattr_migrations')
            ->will($this->returnValue($database));

        $database->expects($this->once())
            ->method('selectCollection')
            ->with('antimattr_migration_versions_test')
            ->will($this->returnValue($collection));

        $cursor = $this->buildMock('MongoCursor');

        $collection->expects($this->once())
            ->method('find')
            ->will($this->returnValue($cursor));

        $cursor->expects($this->once())
            ->method('count')
            ->will($this->returnValue(2));

        $this->assertEquals(2, $this->configuration->getNumberOfExecutedMigrations());
    }

    public function testRegisterMigrationsFromDirectory()
    {
        $this->configuration->setMigrationsNamespace('Example\Migrations\TestAntiMattr\MongoDB');
        $this->assertFalse($this->configuration->hasVersion('20140822185742'));

        $directory = dirname(__DIR__)."/Resources/Migrations/";
        $this->configuration->registerMigrationsFromDirectory($directory);

        $this->assertEquals(3, count($this->configuration->getMigrations()));
        $this->assertEquals(3, count($this->configuration->getAvailableVersions()));
        $this->assertEquals(3, $this->configuration->getNumberOfAvailableMigrations());

        $this->assertTrue($this->configuration->hasVersion('20140822185742'));

        $version = $this->configuration->getVersion('20140822185742');
    }

    /**
     * @expectedException AntiMattr\MongoDB\Migrations\Exception\UnknownVersionException
     */
    public function testGetVersionThrowsUnknownVersionException()
    {
        $this->configuration->getVersion('20140822185742');
    }

    public function testHasVersionMigrated()
    {
        $version1 = $this->buildMock('AntiMattr\MongoDB\Migrations\Version');
        $version2 = $this->buildMock('AntiMattr\MongoDB\Migrations\Version');

        $this->prepareValidConfiguration();

        $collection = $this->buildMock('Doctrine\MongoDB\Collection');
        $database = $this->buildMock('Doctrine\MongoDB\Database');

        $this->connection->expects($this->once())
            ->method('selectDatabase')
            ->with('test_antimattr_migrations')
            ->will($this->returnValue($database));

        $database->expects($this->once())
            ->method('selectCollection')
            ->with('antimattr_migration_versions_test')
            ->will($this->returnValue($collection));

        $version1->expects($this->once())
            ->method('getVersion')
            ->will($this->returnValue('found'));

        $version2->expects($this->once())
            ->method('getVersion')
            ->will($this->returnValue('found2'));

        $cursor = $this->buildMock('MongoCursor');

        $collection->expects($this->at(1))
            ->method('findOne')
            ->with(array('v' => 'found'))
            ->will($this->returnValue('foo'));

        $collection->expects($this->at(2))
            ->method('findOne')
            ->with(array('v' => 'found2'))
            ->will($this->returnValue(null));

        $this->assertTrue($this->configuration->hasVersionMigrated($version1));
        $this->assertFalse($this->configuration->hasVersionMigrated($version2));
    }

    /**
     * @expectedException AntiMattr\MongoDB\Migrations\Exception\ConfigurationValidationException
     */
    public function testValidateThrowsConfigurationValidationException()
    {
        $this->configuration->validate();
    }

    public function testValidate()
    {
        $this->prepareValidConfiguration();
        $this->configuration->validate();
    }

    private function prepareValidConfiguration()
    {
        $directory = dirname(__DIR__)."/Resources/Migrations/";
        $this->configuration->setMigrationsDatabaseName('test_antimattr_migrations');
        $this->configuration->setMigrationsDirectory($directory);
        $this->configuration->setMigrationsNamespace('Example\Migrations\TestAntiMattr\MongoDB');
        $this->configuration->setMigrationsCollectionName('antimattr_migration_versions_test');
    }
}
