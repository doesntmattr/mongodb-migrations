<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Tools\Console\Command;

use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use AntiMattr\MongoDB\Migrations\Migration;
use AntiMattr\MongoDB\Migrations\Tools\Console\Command\VersionCommand;
use AntiMattr\TestCase\AntiMattrTestCase;
use Symfony\Component\Console\Input\ArgvInput;

/**
 * @author Ryan Catlin <ryan.catlin@gmail.com>
 */
class VersionCommandTest extends AntiMattrTestCase
{
    private $command;
    private $output;

    protected function setUp()
    {
        $this->command = new VersionCommandStub();
        $this->output = $this->buildMock('Symfony\Component\Console\Output\OutputInterface');
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidArgumentException()
    {
        // Mocks
        $config = $this->buildMock('AntiMattr\MongoDB\Migrations\Configuration\Configuration');
        $migration = $this->buildMock('AntiMattr\MongoDB\Migrations\Migration');
        $version = $this->buildMock('AntiMattr\MongoDB\Migrations\Version');

        // Variables and objects
        $numVersion = '123456789012';
        $input = new ArgvInput(
            array(
                VersionCommand::NAME,
                $numVersion
            )
        );

        // Set properties on objects
        $this->command->setMigrationConfiguration($config);
        $this->command->setMigration($migration);

        // Run command, run.
        $this->command->run(
            $input,
            $this->output
        );
    }

    /**
     * @expectedException AntiMattr\MongoDB\Migrations\Exception\UnknownVersionException
     */
    public function testUnknownVersionException()
    {
        // Mocks
        $config = $this->buildMock('AntiMattr\MongoDB\Migrations\Configuration\Configuration');
        $migration = $this->buildMock('AntiMattr\MongoDB\Migrations\Migration');
        $version = $this->buildMock('AntiMattr\MongoDB\Migrations\Version');

        // Variables and objects
        $numVersion = '123456789012';
        $input = new ArgvInput(
            array(
                VersionCommand::NAME,
                $numVersion,
                '--add'
            )
        );

        // Set properties on objects
        $this->command->setMigrationConfiguration($config);
        $this->command->setMigration($migration);

         // Expectations
        $config->expects($this->once())
            ->method('hasVersion')
            ->with($numVersion)
            ->will(
                $this->returnValue(false)
            )
        ;

        // Run command, run.
        $this->command->run(
            $input,
            $this->output
        );
    }

    public function testAddVersion()
    {
        // Mocks
        $config = $this->buildMock('AntiMattr\MongoDB\Migrations\Configuration\Configuration');
        $migration = $this->buildMock('AntiMattr\MongoDB\Migrations\Migration');
        $version = $this->buildMock('AntiMattr\MongoDB\Migrations\Version');

        // Variables and objects
        $numVersion = '123456789012';
        $input = new ArgvInput(
            array(
                VersionCommand::NAME,
                $numVersion,
                '--add'
            )
        );

        // Set properties on objects
        $this->command->setMigrationConfiguration($config);
        $this->command->setMigration($migration);

        // Expectations
        $config->expects($this->once())
            ->method('hasVersion')
            ->with($numVersion)
            ->will(
                $this->returnValue(true)
            )
        ;

        $config->expects($this->once())
            ->method('getVersion')
            ->with($numVersion)
            ->will(
                $this->returnValue($version)
            )
        ;

        $config->expects($this->once())
            ->method('hasVersionMigrated')
            ->with($version)
            ->will(
                $this->returnValue(false)
            )
        ;

        $version->expects($this->once())
            ->method('markMigrated')
        ;

        // Run command, run.
        $this->command->run(
            $input,
            $this->output
        );
    }

    public function testDownVersion()
    {
        // Mocks
        $config = $this->buildMock('AntiMattr\MongoDB\Migrations\Configuration\Configuration');
        $migration = $this->buildMock('AntiMattr\MongoDB\Migrations\Migration');
        $version = $this->buildMock('AntiMattr\MongoDB\Migrations\Version');

        // Variables and objects
        $numVersion = '123456789012';
        $input = new ArgvInput(
            array(
                VersionCommand::NAME,
                $numVersion,
                '--delete'
            )
        );

        // Set properties on objects
        $this->command->setMigrationConfiguration($config);
        $this->command->setMigration($migration);

        // Expectations
        $config->expects($this->once())
            ->method('hasVersion')
            ->with($numVersion)
            ->will(
                $this->returnValue(true)
            )
        ;

        $config->expects($this->once())
            ->method('getVersion')
            ->with($numVersion)
            ->will(
                $this->returnValue($version)
            )
        ;

        $config->expects($this->once())
            ->method('hasVersionMigrated')
            ->with($version)
            ->will(
                $this->returnValue(true)
            )
        ;

        $version->expects($this->once())
            ->method('markNotMigrated')
        ;

        // Run command, run.
        $this->command->run(
            $input,
            $this->output
        );
    }
}

class VersionCommandStub extends VersionCommand
{
    private $migration;

    public function setMigration(Migration $migration)
    {
        $this->migration = $migration;
    }
    protected function createMigration(Configuration $configuration)
    {
        return $this->migration;
    }
}
