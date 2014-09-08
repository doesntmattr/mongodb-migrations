<?php

/*
 * This file is part of the AntiMattr MongoDB Migrations Library, a library by Matthew Fitzgerald.
 *
 * (c) 2014 Matthew Fitzgerald
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AntiMattr\MongoDB\Migrations;

use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use AntiMattr\MongoDB\Migrations\Exception\UnknownVersionException;
use AntiMattr\MongoDB\Migrations\Exception\NoMigrationsToExecuteException;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
class Migration
{
    /**
     * The OutputWriter object instance used for outputting information
     *
     * @var OutputWriter
     */
    private $outputWriter;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * Construct a Migration instance
     *
     * @param Configuration $configuration A migration Configuration instance
     */
    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
        $this->outputWriter = $configuration->getOutputWriter();
    }

    /**
     * Run a migration to the current version or the given target version.
     *
     * @param string $to The version to migrate to.
     *
     * @throws AntiMattr\MongoDB\Migrations\Exception\UnknownVersionException
     * @throws AntiMattr\MongoDB\Migrations\Exception\NoMigrationsToExecuteException
     */
    public function migrate($to = null)
    {
        if ($to === null) {
            $to = $this->configuration->getLatestVersion();
        }

        $from = $this->configuration->getCurrentVersion();
        $from = (string) $from;
        $to = (string) $to;

        $migrations = $this->configuration->getMigrations();
        if ( ! isset($migrations[$to]) && $to > 0) {
            throw new UnknownVersionException($to);
        }

        $direction = $from > $to ? 'down' : 'up';
        $migrationsToExecute = $this->configuration->getMigrationsToExecute($direction, $to);

        if ($from === $to && empty($migrationsToExecute) && $migrations) {
            return;
        }

        $this->outputWriter->write(sprintf('Migrating <info>%s</info> to <comment>%s</comment> from <comment>%s</comment>', $direction, $to, $from));

        if (empty($migrationsToExecute)) {
            throw new NoMigrationsToExecuteException('Could not find any migrations to execute.');
        }

        $time = 0;
        foreach ($migrationsToExecute as $version) {
            $versionSql = $version->execute($direction);
            $time += $version->getTime();
        }

        $this->outputWriter->write("\n  <comment>------------------------</comment>\n");
        $this->outputWriter->write(sprintf("  <info>++</info> finished in %s", $time));
        $this->outputWriter->write(sprintf("  <info>++</info> %s migrations executed", count($migrationsToExecute)));
    }
}
