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

        $currentVersion = $configuration->getCurrentVersion();
        if ($currentVersion) {
            $currentVersionFormatted = $configuration->formatVersion($currentVersion).' (<comment>'.$currentVersion.'</comment>)';
        } else {
            $currentVersionFormatted = 0;
        }
        $latestVersion = $configuration->getLatestVersion();
        if ($latestVersion) {
            $latestVersionFormatted = $configuration->formatVersion($latestVersion).' (<comment>'.$latestVersion.'</comment>)';
        } else {
            $latestVersionFormatted = 0;
        }

        $executedMigrations = $configuration->getMigratedVersions();
        $availableMigrations = $configuration->getAvailableVersions();
        $executedUnavailableMigrations = array_diff($executedMigrations, $availableMigrations);
        $numExecutedUnavailableMigrations = count($executedUnavailableMigrations);
        $newMigrations = count($availableMigrations) - count($executedMigrations);

        $output->writeln("\n <info>==</info> Configuration\n");

        $info = array(
            'Name'                              => $configuration->getName() ? $configuration->getName() : 'Doctrine Database Migrations',
            'Database Driver'                   => 'MongoDB',
            'Database Name'                     => $configuration->getMigrationsDatabaseName(),
            'Configuration Source'              => $configuration instanceof AbstractFileConfiguration ? $configuration->getFile() : 'manually configured',
            'Version Collection Name'           => $configuration->getMigrationsCollectionName(),
            'Migrations Namespace'              => $configuration->getMigrationsNamespace(),
            'Migrations Directory'              => $configuration->getMigrationsDirectory(),
            'Current Version'                   => $currentVersionFormatted,
            'Latest Version'                    => $latestVersionFormatted,
            'Executed Migrations'               => count($executedMigrations),
            'Executed Unavailable Migrations'   => $numExecutedUnavailableMigrations > 0 ? '<error>'.$numExecutedUnavailableMigrations.'</error>' : 0,
            'Available Migrations'              => count($availableMigrations),
            'New Migrations'                    => $newMigrations > 0 ? '<question>'.$newMigrations.'</question>' : 0
        );
        foreach ($info as $name => $value) {
            $output->writeln('    <comment>>></comment> '.$name.': '.str_repeat(' ', 35 - strlen($name)).$value);
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

    public function getName()
    {
        return self::NAME;
    }    
}
