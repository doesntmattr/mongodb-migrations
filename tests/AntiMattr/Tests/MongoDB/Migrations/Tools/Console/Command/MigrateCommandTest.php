<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Tools\Console\Command;

use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use AntiMattr\MongoDB\Migrations\Migration;
use AntiMattr\MongoDB\Migrations\Tools\Console\Command\MigrateCommand;
use AntiMattr\MongoDB\Migrations\Version;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;

class MigrateCommandTest extends TestCase
{
    private $command;
    private $output;

    public function setUp(): void
    {
        $this->command = new MigrateCommandStub();
        $this->output = $this->createMock('Symfony\Component\Console\Output\OutputInterface');
    }

    public function testExecuteWithExectedUnavailableVersionAndInteraction()
    {
        // Mocks
        $configuration = $this->createMock('AntiMattr\MongoDB\Migrations\Configuration\Configuration');
        $executedVersion = $this->createMock('AntiMattr\MongoDB\Migrations\Version');
        $migration = $this->createMock('AntiMattr\MongoDB\Migrations\Migration');
        $question = $this->createMock('Symfony\Component\Console\Helper\QuestionHelper');

        // Variables and Objects
        $numVersion = '000123456789';
        $input = new ArgvInput(
            [
                'application-name',
                MigrateCommand::getDefaultName(),
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
        $configuration = $this->createMock('AntiMattr\MongoDB\Migrations\Configuration\Configuration');
        $availableVersion = $this->createMock('AntiMattr\MongoDB\Migrations\Version');
        $migration = $this->createMock('AntiMattr\MongoDB\Migrations\Migration');

        // Variables and Objects
        $numVersion = '000123456789';
        $input = new ArgvInput(
            [
                MigrateCommand::getDefaultName(),
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

    public function testDefaultInteractionWillCancelMigration()
    {
        $migration = $this->createMock(Migration::class);
        $numVersion = '000123456789';

        // We do not expect this to be called
        $migration->expects($this->never())
            ->method('migrate')
            ->with($numVersion)
        ;
        $this->command->setMigration($migration);

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->once())
            ->method('getAvailableVersions')
            ->willReturn([])
        ;
        $this->command->setMigrationConfiguration($configuration);

        $executedVersion = $this->createMock(Version::class);
        $configuration->expects($this->once())
            ->method('getMigratedVersions')
            ->willReturn([$executedVersion])
        ;

        $application = new Application();
        $application->setAutoExit(false);
        $application->add($this->command);

        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs(["\n"]);
        $commandTester->execute(['version' => $numVersion]);

        $this->assertRegExp('/Migration cancelled/', $commandTester->getDisplay());
    }

    public function testDefaultSecondInteractionWillCancelMigration()
    {
        $migration = $this->createMock(Migration::class);
        $numVersion = '000123456789';

        // We do not expect this to be called
        $migration->expects($this->never())
            ->method('migrate')
            ->with($numVersion)
        ;
        $this->command->setMigration($migration);

        $configuration = $this->createMock(Configuration::class);
        $configuration->expects($this->once())
            ->method('getAvailableVersions')
            ->willReturn([])
        ;
        $this->command->setMigrationConfiguration($configuration);

        $executedVersion = $this->createMock(Version::class);
        $configuration->expects($this->once())
            ->method('getMigratedVersions')
            ->willReturn([$executedVersion])
        ;

        $application = new Application();
        $application->setAutoExit(false);
        $application->add($this->command);

        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs(['y', "\n"]);
        $commandTester->execute(['version' => $numVersion]);

        $this->assertRegExp('/Migration cancelled/', $commandTester->getDisplay());
    }
}

class MigrateCommandStub extends MigrateCommand
{
    protected static $defaultName = 'mongodb:migrations:migrate';

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
