<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Tools\Console\Command;

use AntiMattr\MongoDB\Migrations\Tools\Console\Command\ExecuteCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Helper\HelperSet;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * @author Ryan Catlin <ryan.catlin@gmail.com>
 */
class ExecuteCommandTest extends TestCase
{
    private $command;
    private $output;
    private $config;
    private $version;

    public function setUp(): void
    {
        $this->command = new ExecuteCommand();
        $this->output = $this->createMock('Symfony\Component\Console\Output\OutputInterface');
        $this->config = $this->createMock('AntiMattr\MongoDB\Migrations\Configuration\Configuration');
        $this->version = $this->createMock('AntiMattr\MongoDB\Migrations\Version');
    }

    public function testExecuteDownWithoutInteraction()
    {
        // Variables and Objects
        $application = new Application();
        $numVersion = '11235713';
        $interactive = false;

        // Arguments and Options
        $input = new ArgvInput(
            [
                'application-name',
                ExecuteCommand::getDefaultName(),
                $numVersion,
                '--down',
            ]
        );

        // Set properties on objects
        $this->command->setApplication($application);
        $this->command->setMigrationConfiguration($this->config);
        $input->setInteractive($interactive);

        // Expectations
        $this->config->expects($this->once())
            ->method('getVersion')
            ->with($numVersion)
            ->will(
                $this->returnValue($this->version)
            )
        ;

        $this->version->expects($this->once())
            ->method('execute')
            ->with('down', false)
        ;

        // Run command, run.
        $this->command->run(
            $input,
            $this->output
        );
    }

    public function testExecuteUpWithInteraction()
    {
        // Mocks
        $question = $this->createMock('Symfony\Component\Console\Helper\QuestionHelper');

        // Variables and Objects
        $application = new Application();
        $helperSet = new HelperSet(
            [
                'question' => $question,
            ]
        );
        $numVersion = '1234567890';
        $interactive = true;

        // Arguments and Options
        $input = new ArgvInput(
            [
                'application-name',
                ExecuteCommand::getDefaultName(),
                $numVersion,
            ]
        );

        // Set properties on objects
        $application->setHelperSet($helperSet);
        $this->command->setApplication($application);
        $this->command->setMigrationConfiguration($this->config);
        $input->setInteractive($interactive);

        // Expectations
        $this->config->expects($this->once())
            ->method('getVersion')
            ->with($numVersion)
            ->will(
                $this->returnValue($this->version)
            )
        ;

        $question->expects($this->once())
            ->method('ask')
            ->will(
                $this->returnValue(true)
            )
        ;

        $this->version->expects($this->once())
            ->method('execute')
            ->with('up', false)
        ;

        // Run command, run.
        $this->command->run(
            $input,
            $this->output
        );
    }

    public function testDefaultResponseWillCancelExecute()
    {
        // Do NOT expect this to be called
        $this->version->expects($this->never())
            ->method('execute')
            ->with('up', false)
        ;

        $numVersion = '000123456789';
        $this->config->expects($this->once())
            ->method('getVersion')
            ->with($numVersion)
            ->willReturn($this->version)
        ;

        $this->command->setMigrationConfiguration($this->config);

        $application = new Application();
        $application->setAutoExit(false);
        $application->add($this->command);

        $commandTester = new CommandTester($this->command);
        $commandTester->setInputs(["\n"]);
        $commandTester->execute(['version' => $numVersion]);

        $this->assertRegExp('/Migration cancelled/', $commandTester->getDisplay());
    }

    public function testExecuteReplayWithoutInteraction()
    {
        // Variables and Objects
        $application = new Application();
        $numVersion = '11235713';
        $interactive = false;

        // Arguments and Options
        $input = new ArgvInput(
            [
                'application-name',
                ExecuteCommand::getDefaultName(),
                $numVersion,
                '--up',
                '--replay',
            ]
        );

        // Set properties on objects
        $this->command->setApplication($application);
        $this->command->setMigrationConfiguration($this->config);
        $input->setInteractive($interactive);

        // Expectations
        $this->config->expects($this->once())
            ->method('getVersion')
            ->with($numVersion)
            ->will(
                $this->returnValue($this->version)
            )
        ;

        $replay = true;
        $this->version->expects($this->once())
            ->method('execute')
            ->with('up', $replay)
        ;

        // Run command, run.
        $this->command->run(
            $input,
            $this->output
        );
    }
}
