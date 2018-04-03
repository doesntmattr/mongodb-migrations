<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Tools\Console\Command;

use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use AntiMattr\MongoDB\Migrations\Migration;
use AntiMattr\MongoDB\Migrations\Tools\Console\Command\MigrateCommand;
use AntiMattr\TestCase\AntiMattrTestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommandTest extends AntiMattrTestCase
{
    private $command;
    private $output;

    public function setUp()
    {
        $this->command = new MigrateCommandStub();
        $this->output = $this->buildMock('Symfony\Component\Console\Output\OutputInterface');
    }

    public function testExecuteWithExectedUnavailableVersionAndInteraction()
    {
        // Mocks
        $configuration = $this->buildMock('AntiMattr\MongoDB\Migrations\Configuration\Configuration');
        $executedVersion = $this->buildMock('AntiMattr\MongoDB\Migrations\Version');
        $migration = $this->buildMock('AntiMattr\MongoDB\Migrations\Migration');
        $question = $this->buildMock('Symfony\Component\Console\Helper\QuestionHelper');

        // Variables and Objects
        $numVersion = '000123456789';
        $input = new ArgvInput(
            [
                'application-name',
                MigrateCommand::NAME,
                $numVersion,
            ]
        );
        $interactive = true;
        $executedVersions = [$executedVersion];
        $availableVersions = [];
        $application = new Application();
        $helperSet = new HelperSet(
            [
                'question' => $question,
            ]
        );

        // Set properties on objects
        $application->setHelperSet($helperSet);
        $this->command->setApplication($application);
        $input->setInteractive($interactive);
        $this->command->setMigration($migration);
        $this->command->setMigrationConfiguration($configuration);

        // Expectations
        $configuration->expects($this->once())
            ->method('getMigratedVersions')
            ->will(
                $this->returnValue($executedVersions)
            )
        ;

        $configuration->expects($this->once())
            ->method('getAvailableVersions')
            ->will(
                $this->returnValue($availableVersions)
            )
        ;

        $question->expects($this->exactly(2))
            ->method('ask')
            ->will(
                $this->returnValue(true)
            )
        ;

        $migration->expects($this->once())
            ->method('migrate')
            ->with($numVersion)
        ;

        // Run command, run.
        $this->command->run(
            $input,
            $this->output
        );
    }

    public function testExecute()
    {
        // Mocks
        $configuration = $this->buildMock('AntiMattr\MongoDB\Migrations\Configuration\Configuration');
        $availableVersion = $this->buildMock('AntiMattr\MongoDB\Migrations\Version');
        $migration = $this->buildMock('AntiMattr\MongoDB\Migrations\Migration');

        // Variables and Objects
        $numVersion = '000123456789';
        $input = new ArgvInput(
            [
                MigrateCommand::NAME,
                $numVersion,
            ]
        );
        $interactive = false;
        $availableVersions = [$availableVersion];

        // Set properties on objects
        $input->setInteractive($interactive);
        $this->command->setMigration($migration);
        $this->command->setMigrationConfiguration($configuration);

        // Expectations
        $configuration->expects($this->once())
            ->method('getMigratedVersions')
            ->will(
                $this->returnValue([])
            )
        ;

        $configuration->expects($this->once())
            ->method('getAvailableVersions')
            ->will(
                $this->returnValue($availableVersions)
            )
        ;

        $migration->expects($this->once())
            ->method('migrate')
            ->with($numVersion)
        ;

        // Run command, run.
        $this->command->run(
            $input,
            $this->output
        );
    }
}

class MigrateCommandStub extends MigrateCommand
{
    protected $migration;

    public function setMigration(Migration $migration)
    {
        $this->migration = $migration;
    }

    protected function createMigration(Configuration $configuration)
    {
        return $this->migration;
    }

    protected function outputHeader(Configuration $configuration, OutputInterface $output)
    {
        return;
    }
}
