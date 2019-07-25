<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Configuration;

use AntiMattr\MongoDB\Migrations\Configuration\ConfigurationBuilder;
use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use AntiMattr\MongoDB\Migrations\OutputWriter;
use PHPUnit\Framework\TestCase;

class ConfigurationBuilderTest extends TestCase
{
    public function testBuildingConfiguration()
    {
        $conn = $this->createMock('MongoDB\Client');
        $outputWriter = new OutputWriter();
        $onDiskConfig = '';

        $config = ConfigurationBuilder::create()
            ->setConnection($conn)
            ->setOutputWriter($outputWriter)
            ->setOnDiskConfiguration($onDiskConfig)
            ->build();

        $this->assertInstanceOf(Configuration::class, $config);
        $this->assertSame($conn, $config->getConnection());
        $this->assertSame($outputWriter, $config->getOutputWriter());
    }

    public function testBuildingWithYamlConfig()
    {
        $conn = $this->createMock('MongoDB\Client');
        $outputWriter = new OutputWriter();
        $onDiskConfig = dirname(__DIR__) . '/Resources/fixtures/config.yml';

        $config = ConfigurationBuilder::create()
            ->setConnection($conn)
            ->setOutputWriter($outputWriter)
            ->setOnDiskConfiguration($onDiskConfig)
            ->build();

        $this->assertInstanceOf(Configuration::class, $config);
        $this->assertSame($conn, $config->getConnection());
        $this->assertSame($outputWriter, $config->getOutputWriter());

        $this->assertEquals('/path/to/migrations/classes/AntiMattrMigrations', $config->getMigrationsDirectory());
        $this->assertEquals('AntiMattrMigrationsTest', $config->getMigrationsNamespace());
        $this->assertEquals('AntiMattr Sandbox Migrations', $config->getName());
        $this->assertEquals('antimattr_migration_versions_test', $config->getMigrationsCollectionName());
        $this->assertEquals('test_antimattr_migrations', $config->getMigrationsDatabaseName());
        $this->assertEquals('/path/to/migrations/script_directory', $config->getMigrationsScriptDirectory());
    }

    public function testBuildingWithXmlConfig()
    {
        $conn = $this->createMock('MongoDB\Client');
        $outputWriter = new OutputWriter();
        $onDiskConfig = dirname(__DIR__) . '/Resources/fixtures/config.xml';

        $config = ConfigurationBuilder::create()
            ->setConnection($conn)
            ->setOutputWriter($outputWriter)
            ->setOnDiskConfiguration($onDiskConfig)
            ->build();

        $this->assertInstanceOf(Configuration::class, $config);
        $this->assertSame($conn, $config->getConnection());
        $this->assertSame($outputWriter, $config->getOutputWriter());

        $this->assertEquals('/path/to/migrations/classes/AntiMattrMigrations', $config->getMigrationsDirectory());
        $this->assertEquals('AntiMattrMigrationsTest', $config->getMigrationsNamespace());
        $this->assertEquals('AntiMattr Sandbox Migrations', $config->getName());
        $this->assertEquals('antimattr_migration_versions_test', $config->getMigrationsCollectionName());
        $this->assertEquals('test_antimattr_migrations', $config->getMigrationsDatabaseName());
        $this->assertEquals('/path/to/migrations/script_directory', $config->getMigrationsScriptDirectory());
    }
}
