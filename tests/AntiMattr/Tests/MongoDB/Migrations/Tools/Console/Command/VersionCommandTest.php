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
    private $config;
    private $migration;
    private $version;

    protected function setUp()
    {
        $this->command = new VersionCommandStub();
        $this->output = $this->buildMock('Symfony\Component\Console\Output\OutputInterface');
        $this->config = $this->buildMock('AntiMattr\MongoDB\Migrations\Configuration\Configuration');
        $this->migration = $this->buildMock('AntiMattr\MongoDB\Migrations\Migration');
        $this->version = $this->buildMock('AntiMattr\MongoDB\Migrations\Version');

        $this->command->setMigrationConfiguration($this->config);
        $this->command->setMigration($this->migration);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidArgumentException()
    {
        // Variables and objects
        $numVersion = '123456789012';
        $input = new ArgvInput(
            array(
                VersionCommand::NAME,
                $numVersion
            )
        );

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
        // Variables and objects
        $numVersion = '123456789012';
        $input = new ArgvInput(
            array(
                VersionCommand::NAME,
                $numVersion,
                '--add'
            )
        );

         // Expectations
        $this->config->expects($this->once())
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
        // Variables and objects
        $numVersion = '123456789012';
        $input = new ArgvInput(
            array(
                VersionCommand::NAME,
                $numVersion,
                '--add'
            )
        );

        // Expectations
        $this->config->expects($this->once())
            ->method('hasVersion')
            ->with($numVersion)
            ->will(
                $this->returnValue(true)
            )
        ;

        $this->config->expects($this->once())
            ->method('getVersion')
            ->with($numVersion)
            ->will(
                $this->returnValue($this->version)
            )
        ;

        $this->config->expects($this->once())
            ->method('hasVersionMigrated')
            ->with($this->version)
            ->will(
                $this->returnValue(false)
            )
        ;

        $this->version->expects($this->once())
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
        // Variables and objects
        $numVersion = '123456789012';
        $input = new ArgvInput(
            array(
                VersionCommand::NAME,
                $numVersion,
                '--delete'
            )
        );

        // Expectations
        $this->config->expects($this->once())
            ->method('hasVersion')
            ->with($numVersion)
            ->will(
                $this->returnValue(true)
            )
        ;

        $this->config->expects($this->once())
            ->method('getVersion')
            ->with($numVersion)
            ->will(
                $this->returnValue($this->version)
            )
        ;

        $this->config->expects($this->once())
            ->method('hasVersionMigrated')
            ->with($this->version)
            ->will(
                $this->returnValue(true)
            )
        ;

        $this->version->expects($this->once())
            ->method('markNotMigrated')
        ;

        // Run command, run.
        $this->command->run(
            $input,
            $this->output
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testDownOnNonMigratedVersionThrowsInvalidArgumentException()
    {
        // Variables and objects
        $numVersion = '123456789012';
        $input = new ArgvInput(
            array(
                VersionCommand::NAME,
                $numVersion,
                '--delete'
            )
        );

        // Expectations
        $this->config->expects($this->once())
            ->method('hasVersion')
            ->with($numVersion)
            ->will(
                $this->returnValue(true)
            )
        ;

        $this->config->expects($this->once())
            ->method('getVersion')
            ->with($numVersion)
            ->will(
                $this->returnValue($this->version)
            )
        ;

        $this->config->expects($this->once())
            ->method('hasVersionMigrated')
            ->with($this->version)
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

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testUpOnMigratedVersionThrowsInvalidArgumentException()
    {
        // Variables and objects
        $numVersion = '123456789012';
        $input = new ArgvInput(
            array(
                VersionCommand::NAME,
                $numVersion,
                '--add'
            )
        );

        // Expectations
        $this->config->expects($this->once())
            ->method('hasVersion')
            ->with($numVersion)
            ->will(
                $this->returnValue(true)
            )
        ;

        $this->config->expects($this->once())
            ->method('getVersion')
            ->with($numVersion)
            ->will(
                $this->returnValue($this->version)
            )
        ;

        $this->config->expects($this->once())
            ->method('hasVersionMigrated')
            ->with($this->version)
            ->will(
                $this->returnValue(true)
            )
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
