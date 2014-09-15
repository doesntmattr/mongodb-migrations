<?php

/*
 * This file is part of the AntiMattr MongoDB Migrations Library, a library by Matthew Fitzgerald.
 *
 * (c) 2014 Matthew Fitzgerald
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AntiMattr\MongoDB\Migrations\Tools\Console\Command;

use AntiMattr\MongoDB\Migrations\Migration;
use AntiMattr\MongoDB\Migrations\Configuration\AbstractFileConfiguration;
use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
class StatusCommand extends AbstractCommand
{
    const NAME = 'mongodb:migrations:status';

    protected function configure()
    {
        $this
            ->setName($this->getName())
            ->setDescription('View the status of a set of migrations.')
            ->addOption('show-versions', null, InputOption::VALUE_NONE, 'This will display a list of all available migrations and their status')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command outputs the status of a set of migrations:

    <info>%command.full_name%</info>

You can output a list of all available migrations and their status with <comment>--show-versions</comment>:

    <info>%command.full_name% --show-versions</info>
EOT
        );

        parent::configure();
    }

    /**
     * @param Symfony\Component\Console\Input\InputInterface
     * @param Symfony\Component\Console\Output\OutputInterface
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $configuration = $this->getMigrationConfiguration($input, $output);
        $configMap = $configuration->getDetailsMap();

        // Format current version string
        $currentVersion = $configMap['current_version'];
        if ($currentVersion) {
            $currentVersionFormatted = sprintf(
                '%s (<comment>%s</comment>)',
                Configuration::formatVersion($currentVersion),
                $currentVersion
            );
        } else {
            $currentVersionFormatted = 0;
        }

        // Format latest version string
        $latestVersion = $configMap['latest_version'];
        if ($latestVersion) {
            $latestVersionFormatted = sprintf(
                '%s (<comment>%s</comment>)',
                Configuration::formatVersion($latestVersion),
                $latestVersion
            );
        } else {
            $latestVersionFormatted = 0;
        }

        $output->writeln("\n <info>==</info> Configuration\n");

        $numExecutedUnavailableMigrations = $configMap['num_executed_unavailable_migrations'];
        $numNewMigrations = $configMap['num_new_migrations'];

        $info = array(
            'Name'                              => $configMap['name'],
            'Database Driver'                   => $configMap['database_driver'],
            'Database Name'                     => $configMap['migrations_database_name'],
            'Configuration Source'              => $configuration instanceof AbstractFileConfiguration ? $configuration->getFile() : 'manually configured',
            'Version Collection Name'           => $configMap['migrations_collection_name'],
            'Migrations Namespace'              => $configMap['migrations_namespace'],
            'Migrations Directory'              => $configMap['migrations_directory'],
            'Current Version'                   => $currentVersionFormatted,
            'Latest Version'                    => $latestVersionFormatted,
            'Executed Migrations'               => $configMap['num_executed_migrations'],
            'Executed Unavailable Migrations'   => $numExecutedUnavailableMigrations > 0 ? '<error>'.$numExecutedUnavailableMigrations.'</error>' : 0,
            'Available Migrations'              => $configMap['num_available_migrations'],
            'New Migrations'                    => $numNewMigrations > 0 ? '<question>'.$numNewMigrations.'</question>' : 0
        );
        foreach ($info as $name => $value) {
            $this->writeInfoLine($output, $name, $value);
        }

        $showVersions = $input->getOption('show-versions') ? true : false;
        if ($showVersions === true) {
            if ($migrations = $configuration->getMigrations()) {
                $output->writeln("\n <info>==</info> Available Migration Versions\n");
                $migratedVersions = $configuration->getMigratedVersions();
                foreach ($migrations as $version) {
                    $isMigrated = in_array($version->getVersion(), $migratedVersions);
                    $status = $isMigrated ? '<info>migrated</info>' : '<error>not migrated</error>';
                    $output->writeln('    <comment>>></comment> '.$configuration->formatVersion($version->getVersion()).' (<comment>'.$version->getVersion().'</comment>)'.str_repeat(' ', 30 - strlen($name)).$status);
                }
            }

            if ($executedUnavailableMigrations) {
                $output->writeln("\n <info>==</info> Previously Executed Unavailable Migration Versions\n");
                foreach ($executedUnavailableMigrations as $executedUnavailableMigration) {
                    $output->writeln('    <comment>>></comment> '.$configuration->formatVersion($executedUnavailableMigration).' (<comment>'.$executedUnavailableMigration.'</comment>)');
                }
            }
        }
    }

    /**
     * @param string $name
     * @param string $value
     */
    protected function writeInfoLine(OutputInterface $output, $name, $value)
    {
        $whitespace = str_repeat(' ', 35 - strlen($name));
        $output->writeln(
            sprintf(
                '    <comment>>></comment> %s: %s%s',
                $name,
                $whitespace,
                $value
            )
        );
    }

    public function getName()
    {
        return self::NAME;
    }
}
