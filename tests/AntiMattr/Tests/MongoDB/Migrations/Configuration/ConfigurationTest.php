<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Configuration;

use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    private $configuration;
    private $connection;

    protected function setUp(): void
    {
        $this->connection = $this->createMock('MongoDB\Client');
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

        $expectedCollection = $this->createMock('MongoDB\Collection');
        $database = $this->createMock('MongoDB\Database');

        $this->connection->expects($this->once())
            ->method('selectDatabase')
            ->with('test_antimattr_migrations')
            ->willReturn($database);

        $database->expects($this->once())
            ->method('selectCollection')
            ->with('antimattr_migration_versions_test')
            ->willReturn($expectedCollection);

        $collection = $this->configuration->getCollection();
        $this->assertEquals($expectedCollection, $collection);
    }

    public function testGetCurrentVersion()
    {
        $this->prepareValidConfiguration();

        $directory = dirname(__DIR__) . '/Resources/Migrations/';
        $this->configuration->registerMigrationsFromDirectory($directory);

        $collection = $this->createMock('MongoDB\Collection');
        $database = $this->createMock('MongoDB\Database');

        $this->connection->expects($this->once())
            ->method('selectDatabase')
            ->with('test_antimattr_migrations')
            ->willReturn($database);

        $database->expects($this->once())
            ->method('selectCollection')
            ->with('antimattr_migration_versions_test')
            ->willReturn($collection);

        $cursor = $this->createMock('AntiMattr\Tests\MongoDB\Migrations\Configuration\CursorStub');

        $in = ['v' => ['$in' => ['20140822185742', '20140822185743', '20140822185744']]];
        $options = ['sort' => ['v' => -1], 'limit' => 1];

        $collection->expects($this->once())
            ->method('find')
            ->with($in, $options)
            ->willReturn($cursor);

        $cursor->expects($this->once())
            ->method('toArray')
            ->willReturn([['v' => '20140822185743']]);

        $version = $this->configuration->getCurrentVersion();

        $this->assertEquals($version, '20140822185743');
    }

    public function testGetDatabase()
    {
        $this->configuration->setMigrationsDatabaseName('test_antimattr_migrations');

        $expectedDatabase = $this->createMock('MongoDB\Database');

        $this->connection->expects($this->once())
            ->method('selectDatabase')
            ->with('test_antimattr_migrations')
            ->willReturn($expectedDatabase);

        $database = $this->configuration->getDatabase();
        $this->assertEquals($expectedDatabase, $database);
    }

    public function testGetMigratedVersions()
    {
        $this->prepareValidConfiguration();

        $collection = $this->createMock('MongoDB\Collection');
        $database = $this->createMock('MongoDB\Database');

        $this->connection->expects($this->once())
            ->method('selectDatabase')
            ->with('test_antimattr_migrations')
            ->willReturn($database);

        $database->expects($this->once())
            ->method('selectCollection')
            ->with('antimattr_migration_versions_test')
            ->willReturn($collection);

        $foundVersions = [
            ['v' => 'found1'],
            ['v' => 'found2'],
        ];

        $expectedVersions = [
            'found1',
            'found2',
        ];

        $collection->expects($this->once())
            ->method('find')
            ->willReturn($foundVersions);

        $versions = $this->configuration->getMigratedVersions();
        $this->assertEquals($expectedVersions, $versions);
    }

    public function testGetNumberOfExecutedMigrations()
    {
        $this->prepareValidConfiguration();

        $collection = $this->createMock('MongoDB\Collection');
        $database = $this->createMock('MongoDB\Database');

        $this->connection->expects($this->once())
            ->method('selectDatabase')
            ->with('test_antimattr_migrations')
            ->willReturn($database);

        $database->expects($this->once())
            ->method('selectCollection')
            ->with('antimattr_migration_versions_test')
            ->willReturn($collection);

        $collection->expects($this->once())
            ->method('countDocuments')
            ->willReturn(2);

        $this->assertEquals(2, $this->configuration->getNumberOfExecutedMigrations());
    }

    public function testRegisterMigrationsFromDirectory()
    {
        $this->configuration->setMigrationsNamespace('Example\Migrations\TestAntiMattr\MongoDB');
        $this->assertFalse($this->configuration->hasVersion('20140822185742'));

        $directory = dirname(__DIR__) . '/Resources/Migrations/';
        $this->configuration->registerMigrationsFromDirectory($directory);

        $this->assertEquals(3, count($this->configuration->getMigrations()));
        $this->assertEquals(3, count($this->configuration->getAvailableVersions()));
        $this->assertEquals(3, $this->configuration->getNumberOfAvailableMigrations());

        $this->assertTrue($this->configuration->hasVersion('20140822185742'));

        $version = $this->configuration->getVersion('20140822185742');
    }

    /**
     * @expectedException \AntiMattr\MongoDB\Migrations\Exception\UnknownVersionException
     */
    public function testGetVersionThrowsUnknownVersionException()
    {
        $this->configuration->getVersion('20140822185742');
    }

    public function testHasVersionMigrated()
    {
        $version1 = $this->createMock('AntiMattr\MongoDB\Migrations\Version');
        $version2 = $this->createMock('AntiMattr\MongoDB\Migrations\Version');

        $this->prepareValidConfiguration();

        $collection = $this->createMock('MongoDB\Collection');
        $database = $this->createMock('MongoDB\Database');

        $this->connection->expects($this->once())
            ->method('selectDatabase')
            ->with('test_antimattr_migrations')
            ->willReturn($database);

        $database->expects($this->once())
            ->method('selectCollection')
            ->with('antimattr_migration_versions_test')
            ->willReturn($collection);

        $version1->expects($this->once())
            ->method('getVersion')
            ->willReturn('found');

        $version2->expects($this->once())
            ->method('getVersion')
            ->willReturn('found2');

        $collection->expects($this->at(1))
            ->method('findOne')
            ->with(['v' => 'found'])
            ->willReturn('foo');

        $collection->expects($this->at(2))
            ->method('findOne')
            ->with(['v' => 'found2'])
            ->willReturn(null);

        $this->assertTrue($this->configuration->hasVersionMigrated($version1));
        $this->assertFalse($this->configuration->hasVersionMigrated($version2));
    }

    /**
     * @expectedException \AntiMattr\MongoDB\Migrations\Exception\ConfigurationValidationException
     */
    public function testValidateThrowsConfigurationValidationException()
    {
        $this->configuration->validate();
    }

    public function testGetUnavailableMigratedVersions()
    {
        $configuration = $this->getMockBuilder('AntiMattr\MongoDB\Migrations\Configuration\Configuration')
            ->disableOriginalConstructor()
            ->setMethods(['getMigratedVersions', 'getAvailableVersions'])
            ->getMock();
        $configuration->expects($this->once())
            ->method('getMigratedVersions')
            ->willReturn(['1', '2']);
        $configuration->expects($this->once())
            ->method('getAvailableVersions')
            ->willReturn(['2', '3']);

        $this->assertEquals(['1'], $configuration->getUnavailableMigratedVersions());
    }

    public function testValidate()
    {
        $this->prepareValidConfiguration();
        self::assertNull($this->configuration->validate());
    }

    /**
     * @expectedException \DomainException
     * @expectedExceptionMessage Unexpected duplicate version records in the database
     */
    public function testDuplicateInGetMigratedTimestampThrowsException()
    {
        $this->prepareValidConfiguration();

        $collection = $this->createMock('MongoDB\Collection');
        $database = $this->createMock('MongoDB\Database');

        $this->connection->expects($this->once())
            ->method('selectDatabase')
            ->with('test_antimattr_migrations')
            ->willReturn($database);

        $database->expects($this->once())
            ->method('selectCollection')
            ->with('antimattr_migration_versions_test')
            ->willReturn($collection);

        $cursor = $this->createMock('AntiMattr\Tests\MongoDB\Migrations\Configuration\CursorStub');

        $collection->expects($this->once())
            ->method('find')
            ->willReturn($cursor);

        $cursor->expects($this->once())
            ->method('toArray')
            ->willReturn([['v' => '20140822185743'], ['v' => '20140822185743']]);

        $this->configuration->getMigratedTimestamp('1');
    }

    public function testGetMigratedTimestamp()
    {
        $this->prepareValidConfiguration();

        $collection = $this->createMock('MongoDB\Collection');
        $database = $this->createMock('MongoDB\Database');

        $this->connection->expects($this->once())
            ->method('selectDatabase')
            ->with('test_antimattr_migrations')
            ->willReturn($database);

        $database->expects($this->once())
            ->method('selectCollection')
            ->with('antimattr_migration_versions_test')
            ->willReturn($collection);

        $cursor = $this->createMock('AntiMattr\Tests\MongoDB\Migrations\Configuration\CursorStub');

        $collection->expects($this->once())
            ->method('find')
            ->willReturn($cursor);

        $cursor->expects($this->once())
            ->method('toArray')
            ->willReturn([['v' => '20140822185743', 't' => new \DateTime()]]);

        $this->assertTrue(is_numeric($this->configuration->getMigratedTimestamp('1')));
    }

    private function prepareValidConfiguration()
    {
        $directory = dirname(__DIR__) . '/Resources/Migrations/';
        $this->configuration->setMigrationsDatabaseName('test_antimattr_migrations');
        $this->configuration->setMigrationsDirectory($directory);
        $this->configuration->setMigrationsNamespace('Example\Migrations\TestAntiMattr\MongoDB');
        $this->configuration->setMigrationsCollectionName('antimattr_migration_versions_test');
    }
}

/**
 * A stub implementation for the MongoDB\Driver\Cursor class as that one is final
 * The MongoDB\Driver\Cursor class encapsulates the results of a MongoDB command or query and may be returned by MongoDB\Driver\Manager::executeCommand() or MongoDB\Driver\Manager::executeQuery(), respectively.
 *
 * @see https://php.net/manual/en/class.mongodb-driver-cursor.php
 */
class CursorStub
{
    /**
     * Create a new Cursor
     * MongoDB\Driver\Cursor objects are returned as the result of an executed command or query and cannot be constructed directly.
     *
     * @see https://php.net/manual/en/mongodb-driver-cursor.construct.php
     */
    private function __construct()
    {
    }

    /**
     * Returns the MongoDB\Driver\CursorId associated with this cursor. A cursor ID cursor uniquely identifies the cursor on the server.
     *
     * @see https://php.net/manual/en/mongodb-driver-cursor.getid.php
     *
     * @return CursorId for this Cursor
     *
     * @throws InvalidArgumentException on argument parsing errors
     */
    public function getId()
    {
    }

    /**
     * Returns the MongoDB\Driver\Server associated with this cursor. This is the server that executed the query or command.
     *
     * @see https://php.net/manual/en/mongodb-driver-cursor.getserver.php
     *
     * @return Server for this Cursor
     *
     * @throws InvalidArgumentException on argument parsing errors
     */
    public function getServer()
    {
    }

    /**
     * Checks if a cursor is still alive.
     *
     * @see https://php.net/manual/en/mongodb-driver-cursor.isdead.php
     *
     * @return bool
     *
     * @throws InvalidArgumentException On argument parsing errors
     */
    public function isDead()
    {
    }

    /**
     * Sets a type map to use for BSON unserialization.
     *
     * @see https://php.net/manual/en/mongodb-driver-cursor.settypemap.php
     *
     * @throws InvalidArgumentException On argument parsing errors or if a class in the type map cannot
     *                                  be instantiated or does not implement MongoDB\BSON\Unserializable
     */
    public function setTypeMap(array $typemap)
    {
    }

    /**
     * Returns an array of all result documents for this cursor.
     *
     * @see https://php.net/manual/en/mongodb-driver-cursor.toarray.php
     *
     * @return array
     *
     * @throws InvalidArgumentException On argument parsing errors
     */
    public function toArray()
    {
    }
}
