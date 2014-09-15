<?php

namespace AntiMattr\Tests\MongoDB\Migrations\Tools\Console\Command;

use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use AntiMattr\MongoDB\Migrations\Migration;
use AntiMattr\MongoDB\Migrations\Tools\Console\Command\StatusCommand;
use AntiMattr\TestCase\AntiMattrTestCase;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Output\OutputInterface;

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
        $databaseDriver = 'MongoDB';
        $migrationsDatabaseName = ' migrations-database-name';
        $migrationsCollectionName = 'migrations-collection-name';
        $migrationsNamespace = 'migrations-namespace';
        $migrationsDirectory = 'migrations-directory';
        $currentVersion = 'abcdefghijk';
        $currentVersionFormatted = 'abcdefghijk (<comment>abcdefghijk</comment>)';
        $latestVersion = '1234567890';
        $latestVersionFormatted = '1234567890 (<comment>1234567890</comment>)';
        $executedMigrations = array();
        $availableMigrations = array();
        $numExecutedMigrations = 0;
        $numExecutedUnavailableMigrations = 0;
        $numAvailableMigrations = 0;
        $numNewMigrations = 0;

        // Expectations
        $this->config->expects($this->once())
            ->method('getDetailsMap')
            ->will(
                $this->returnValue(
                    array(
                        'name' => $configName,
                        'database_driver' => $databaseDriver,
                        'migrations_database_name' => $migrationsDatabaseName,
                        'migrations_collection_name' => $migrationsCollectionName,
                        'migrations_namespace' => $migrationsNamespace,
                        'migrations_directory' => $migrationsDirectory,
                        'current_version' => $currentVersion,
                        'latest_version' => $latestVersion,
                        'num_executed_migrations' => $numExecutedMigrations,
                        'num_executed_unavailable_migrations' => $numExecutedUnavailableMigrations,
                        'num_available_migrations' => $numAvailableMigrations,
                        'num_new_migrations' => $numNewMigrations,
                    )
                )
            )
        ;
        $this->output->expects($this->at(0))
            ->method('writeln')
            ->with(
                "\n <info>==</info> Configuration\n"
            )
        ;
        $this->output->expects($this->at(1))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::%s',
                    'Name',
                    $configName
                )
            )
        ;
        $this->output->expects($this->at(2))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::%s',
                    'Database Driver',
                    'MongoDB'
                )
            )
        ;
        $this->output->expects($this->at(3))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::%s',
                    'Database Name',
                    $migrationsDatabaseName
                )
            )
        ;
        $this->output->expects($this->at(4))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::%s',
                    'Configuration Source',
                    'manually configured'
                )
            )
        ;
        $this->output->expects($this->at(5))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::%s',
                    'Version Collection Name',
                    $migrationsCollectionName
                )
            )
        ;
        $this->output->expects($this->at(6))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::%s',
                    'Migrations Namespace',
                    $migrationsNamespace
                )
            )
        ;
        $this->output->expects($this->at(7))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::%s',
                    'Migrations Directory',
                    $migrationsDirectory
                )
            )
        ;
        $this->output->expects($this->at(8)) // current version formatted
            ->method('writeln')
        ;
        $this->output->expects($this->at(9)) // latest version formatted
            ->method('writeln')
        ;
        $this->output->expects($this->at(10))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::%s',
                    'Executed Migrations',
                    $numExecutedMigrations
                )
            )
        ;
        $this->output->expects($this->at(11))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::%s',
                    'Executed Unavailable Migrations',
                    $numExecutedUnavailableMigrations
                )
            )
        ;
        $this->output->expects($this->at(12))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::%s',
                    'Available Migrations',
                    $numAvailableMigrations
                )
            )
        ;
        $this->output->expects($this->at(13))
            ->method('writeln')
            ->with(
                sprintf(
                    '%s::%s',
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
    private $configuration;

    public function setMigrationConfiguration(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function getMigrationConfiguration()
    {
        return $this->configuration;
    }

    /**
     * Overwite complex string passed to OutputInterface::writeln
     * so we can set simple expectations on the value passed to this function.
     */
    protected function writeInfoLine(OutputInterface $output, $name, $value)
    {
        $output->writeln($name . "::" . $value);
    }
}
