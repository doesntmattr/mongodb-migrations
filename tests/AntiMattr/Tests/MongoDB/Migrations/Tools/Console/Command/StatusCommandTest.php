<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Tools\Console\Command;

use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use AntiMattr\MongoDB\Migrations\Migration;
use AntiMattr\MongoDB\Migrations\Tools\Console\Command\StatusCommand;
use AntiMattr\TestCase\AntiMattrTestCase;
use Symfony\Component\Console\Input\ArgvInput;

/**
 * @author Ryan Catlin <ryan.catlin@gmail.com>
 */
class StatusCommandTest extends AntiMattrTestCase
{
    private $command;
    private $output;
    private $config;
    private $migration;
    private $version;

    protected function setUp()
    {
        $this->command = new StatusCommandStub();
        $this->output = $this->buildMock('Symfony\Component\Console\Output\OutputInterface');
        $this->config = $this->buildMock('AntiMattr\MongoDB\Migrations\Configuration\Configuration');
        $this->migration = $this->buildMock('AntiMattr\MongoDB\Migrations\Migration');
        $this->version = $this->buildMock('AntiMattr\MongoDB\Migrations\Version');

        $this->command->setMigrationConfiguration($this->config);
        $this->command->setMigration($this->migration);
    }

    public function testExecuteWithoutShowingVersions()
    {
        $input = new ArgvInput(
            array(
                StatusCommand::NAME
            )
        );

        $configValues = array(
            'name' => 'config-name',
            'migrationsDatabaseName' => 'database-name',
            'migrationsCollectionName' => 'collection-name'
        );
        $configName = 'config-name';
        $migrationsDatabaseName = ' migrations-database-name';
        $migrationsCollectionName = 'migrations-collection-name';
        $migrationsNamespace = 'migrations-namespace';
        $migrationsDirectory = 'migrations-directory';
        $executedMigrations = array();
        $availableMigrations = array();
        $numExecutedMigrations = 0;
        $numAvailableMigrations = 0;
        $numNewMigrations = 0;

        // Expectations
        $configuration->expects($this->once())
            ->method('getMigratedVersions')
            ->will(
                $this->returnValue($executedMigrations)
            )
        ;

        $configuration->expects($this->once())
            ->method('getAvailableMigrations')
            ->will(
                $this->returnValue($availableMigrations)
            )
        ;

        $configuration->expects($this->once())
            ->method('getName')
            ->will(
                $this->returnValue($configName)
            )
        ;

        $configuration->expects($this->once())
            ->method('getMigrationsDatabaseName')
            ->will(
                $this->returnValue($migrationsDatabaseName)
            )
        ;

        $configuration->expects($this->once())
            ->method('getMigrationsCollectionName')
            ->will(
                $this->returnValue($migrationsCollectionName)
            )
        ;

        $configuration->expects($this->once())
            ->method('getMigrationsNamespace')
            ->will(
                $this->returnValue($migrationsNamespace)
            )
        ;

        $configuration->expects($this->once())
            ->method('getMigrationsDirectory')
            ->will(
                $this->returnValue($migrationsDirectory)
            )
        ;

        $this->output->expects($this->at(0))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::%s',
                    'Name',
                    $configName
                )
            )
        ;
        $this->output->expects($this->at(1))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::%s',
                    'Database Driver',
                    'MongoDB'
                )
            )
        ;
        $this->output->expects($this->at(2))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::%s',
                    'Database Name'
                )
            )
        ;
        $this->output->expects($this->at(3))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::%s',
                    'Configuration Source',
                    'manually configured'
                )
            )
        ;
        $this->output->expects($this->at(4))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::%s',
                    'Version Collection Name',
                    $migrationsCollectionName
                )
            )
        ;
        $this->output->expects($this->at(5))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::%s',
                    'Migrations Namespace',
                    $migrationsNamespace
                )
            )
        ;
        $this->output->expects($this->at(6))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::%s',
                    'Migrations Directory',
                    $migrationsDirectory
                )
            )
        ;
        $this->output->expects($this->at(7)) // current version formatted
            ->method('writeln')
        ;
        $this->output->expects($this->at(8)) // latest version formatted
            ->method('writeln')
        ;
        $this->output->expects($this->at(9))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::%s',
                    'Executed Migrations',
                    $numExecutedMigrations
                )
            )
        ;
        $this->output->expects($this->at(10))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::%s',
                    'Executed Unavailable Migrations',
                    0
                )
            )
        ;
        $this->output->expects($this->at(11))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::%s',
                    'Available Migrations',
                    $numAvailableMigrations
                )
            )
        ;
        $this->output->expects($this->at(12))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::<question>%s</question>',
                    'New Migrations',
                    $numNewMigrations
                )
            )
        ;

        // Run command, run.
        $this->command->run(
            $input,
            $this->output
        );
    }
}

class StatusCommandStub extends StatusCommand
{
    private $migration;

    public function setMigrationConfiguration(Migration $migration)
    {
        $this->migration = $migration;
    }

    public function getMigrationConfiguration()
    {
        return $this->migration;
    }

    /**
     * Overwite complex string passed to OutputInterface::writeln
     * so we can set simple expectations on the value passed to this function.
     */
    protected function writeInfoLine($name, $value)
    {
        $output->writeln($name . "::" . $value);
    }
}
