<?php

namespace AntiMattr\Tests\MongoDB\Migrations;

use AntiMattr\MongoDB\Migrations\Migration;
use PHPUnit\Framework\TestCase;

class MigrationTest extends TestCase
{
    private $configuration;
    private $migration;
    private $outputWriter;

    protected function setUp(): void
    {
        $this->configuration = $this->createMock('AntiMattr\MongoDB\Migrations\Configuration\Configuration');
        $this->outputWriter = $this->createMock('AntiMattr\MongoDB\Migrations\OutputWriter');

        $this->configuration->expects($this->once())
            ->method('getOutputWriter')
            ->will($this->returnValue($this->outputWriter));

        $this->migration = new Migration($this->configuration);
    }

    /**
     * @expectedException \AntiMattr\MongoDB\Migrations\Exception\UnknownVersionException
     */
    public function testMigrateThrowsUnknownVersionException()
    {
        $this->migration->migrate('1');
    }

    public function testMigrateHasNothingOutstanding()
    {
        $this->configuration->expects($this->once())
            ->method('getCurrentVersion')
            ->will($this->returnValue('1'));

        $expectedMigrations = [
            '1' => 'foo',
        ];

        $this->configuration->expects($this->once())
            ->method('getMigrations')
            ->will($this->returnValue($expectedMigrations));

        $this->outputWriter->expects($this->never())
            ->method('write');

        $this->migration->migrate('1');
    }

    /**
     * @expectedException \AntiMattr\MongoDB\Migrations\Exception\NoMigrationsToExecuteException
     */
    public function testMigrateButNoMigrationsFound()
    {
        $this->configuration->expects($this->once())
            ->method('getCurrentVersion')
            ->will($this->returnValue('1'));

        $expectedMigrations = [
            '0' => 'foo',
            '1' => 'foo',
            '2' => 'foo',
        ];

        $this->configuration->expects($this->once())
            ->method('getMigrations')
            ->will($this->returnValue($expectedMigrations));

        $this->outputWriter->expects($this->once())
            ->method('write');

        $this->migration->migrate('2');
    }

    public function testMigrate()
    {
        $this->configuration->expects($this->once())
            ->method('getLatestVersion')
            ->will($this->returnValue('2'));

        $this->configuration->expects($this->once())
            ->method('getCurrentVersion')
            ->will($this->returnValue('1'));

        $expectedMigrations = [
            '0' => 'foo',
            '1' => 'foo',
            '2' => 'foo',
        ];

        $this->configuration->expects($this->once())
            ->method('getMigrations')
            ->will($this->returnValue($expectedMigrations));

        $version = $this->createMock('AntiMattr\MongoDB\Migrations\Version');

        $this->configuration->expects($this->once())
            ->method('getMigrationsToExecute')
            ->will($this->returnValue(['2' => $version]));

        $this->outputWriter->expects($this->exactly(4))
            ->method('write');

        $this->migration->migrate();
    }
}
