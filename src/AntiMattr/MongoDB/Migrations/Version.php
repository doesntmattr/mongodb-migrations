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

use AntiMattr\MongoDB\Migrations\Collection\Statistics;
use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use AntiMattr\MongoDB\Migrations\Exception\AbortException;
use AntiMattr\MongoDB\Migrations\Exception\SkipException;
use Exception;
use MongoDB\BSON\UTCDateTime;
use MongoDB\Collection;
use MongoDB\Database;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
class Version
{
    const STATE_NONE = 0;
    const STATE_PRE = 1;
    const STATE_EXEC = 2;
    const STATE_POST = 3;

    /**
     * @var string
     */
    private $class;

    /**
     * @var \AntiMattr\MongoDB\Migrations\Configuration\Configuration
     */
    private $configuration;

    /**
     * @var \MongoDB\Database
     */
    private $db;

    /**
     * @var \AntiMattr\MongoDB\Migrations\AbstractMigration
     */
    protected $migration;

    /**
     * @var \AntiMattr\MongoDB\Migrations\OutputWriter
     */
    private $outputWriter;

    /**
     * The version in timestamp format (YYYYMMDDHHMMSS).
     *
     * @var int
     */
    private $version;

    /**
     * @var \AntiMattr\MongoDB\Migrations\Collection\Statistics[]
     */
    private $statistics = [];

    /**
     * @var int
     */
    private $time;

    /**
     * @var int
     */
    protected $state = self::STATE_NONE;

    public function __construct(Configuration $configuration, $version, $class)
    {
        $this->configuration = $configuration;
        $this->outputWriter = $configuration->getOutputWriter();
        $this->class = $class;
        $this->db = $configuration->getDatabase();
        $this->migration = $this->createMigration();
        $this->version = $version;
    }

    /**
     * @return \AntiMattr\MongoDB\Migrations\Configuration\Configuration $configuration
     */
    public function getConfiguration()
    {
        return $this->configuration;
    }

    public function getExecutionState()
    {
        switch ($this->state) {
            case self::STATE_PRE:
                return 'Pre-Checks';
            case self::STATE_POST:
                return 'Post-Checks';
            case self::STATE_EXEC:
                return 'Execution';
            default:
                return 'No State';
        }
    }

    /**
     * @return \AntiMattr\MongoDB\Migrations\AbstractMigration
     */
    public function getMigration()
    {
        return $this->migration;
    }

    /**
     * @return bool
     */
    public function isMigrated()
    {
        return $this->configuration->hasVersionMigrated($this);
    }

    /**
     * Returns the time this migration version took to execute.
     *
     * @return int $time The time this migration version took to execute
     */
    public function getTime()
    {
        return $this->time;
    }

    /**
     * @return string $version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * @param \MongoDB\Collection
     */
    public function analyze(Collection $collection)
    {
        $statistics = $this->createStatistics();
        $statistics->setDatabase($this->db);
        $statistics->setCollection($collection);
        $name = $collection->getCollectionName();
        $this->statistics[$name] = $statistics;

        try {
            $statistics->updateBefore();
        } catch (\Exception $e) {
            $message = sprintf('     <info>Warning during %s: %s</info>',
                $this->getExecutionState(),
                $e->getMessage()
            );

            $this->outputWriter->write($message);
        }
    }

    /**
     * Execute this migration version up or down.
     *
     * @param string $direction The direction to execute the migration
     * @param bool   $replay    If the migration is being replayed
     *
     * @throws \Exception when migration fails
     */
    public function execute($direction, $replay = false)
    {
        if ('down' === $direction && $replay) {
            throw new AbortException('Cannot run \'down\' and replay it. Use replay with \'up\'');
        }

        try {
            $start = microtime(true);

            $this->state = self::STATE_PRE;

            $this->migration->{'pre' . ucfirst($direction)}($this->db);

            if ('up' === $direction) {
                $this->outputWriter->write("\n" . sprintf('  <info>++</info> migrating <comment>%s</comment>', $this->version) . "\n");
            } else {
                $this->outputWriter->write("\n" . sprintf('  <info>--</info> reverting <comment>%s</comment>', $this->version) . "\n");
            }

            $this->state = self::STATE_EXEC;

            $this->migration->$direction($this->db);

            $this->updateStatisticsAfter();

            if (!$this->configuration->isDryRun()) {
                if ('up' === $direction) {
                    $this->markMigrated($replay);
                } else {
                    $this->markNotMigrated();
                }
            }

            $this->summarizeStatistics();

            $this->state = self::STATE_POST;
            $this->migration->{'post' . ucfirst($direction)}($this->db);

            $end = microtime(true);
            $this->time = round($end - $start, 2);
            if ('up' === $direction) {
                $this->outputWriter->write(sprintf("\n  <info>++</info> migrated (%ss)", $this->time));
            } else {
                $this->outputWriter->write(sprintf("\n  <info>--</info> reverted (%ss)", $this->time));
            }

            $this->state = self::STATE_NONE;
        } catch (SkipException $e) {
            // now mark it as migrated
            if ('up' === $direction) {
                $this->markMigrated();
            } else {
                $this->markNotMigrated();
            }

            $this->outputWriter->write(sprintf("\n  <info>SS</info> skipped (Reason: %s)", $e->getMessage()));

            $this->state = self::STATE_NONE;
        } catch (\Exception $e) {
            $this->outputWriter->write(sprintf(
                '<error>Migration %s failed during %s. Error %s</error>',
                $this->version, $this->getExecutionState(), $e->getMessage()
            ));

            $this->state = self::STATE_NONE;
            throw $e;
        }
    }

    /**
     * @param \MongoDB\Database
     * @param string $file
     *
     * @return array
     *
     * @throws RuntimeException
     * @throws InvalidArgumentException
     * @throws Exception
     */
    public function executeScript(Database $db, $file)
    {
        $scripts = $this->configuration->getMigrationsScriptDirectory();
        if (null === $scripts) {
            throw new \RuntimeException('Missing Configuration for migrations script directory');
        }

        $path = realpath($scripts . '/' . $file);
        if (!file_exists($path)) {
            throw new \InvalidArgumentException(sprintf('Could not execute %s. File does not exist.', $path));
        }

        try {
            $js = file_get_contents($path);
            if (false === $js) {
                throw new \Exception('file_get_contents returned false');
            }
        } catch (\Exception $e) {
            throw $e;
        }

        return $db->command(['$eval' => $js, 'nolock' => true]);
    }

    /**
     * markMigrated.
     *
     * @param bool $replay This is a replayed migration, do an update instead of an insert
     */
    public function markMigrated($replay = false)
    {
        $this->configuration->createMigrationCollection();
        $collection = $this->configuration->getCollection();

        $document = ['v' => $this->version, 't' => $this->createMongoTimestamp()];

        if ($replay) {
            $query = ['v' => $this->version];
            // If the user asked for a 'replay' of a migration that
            // has not been run, it will be inserted anew
            $options = ['upsert' => true];
            $collection->updateOne($query, $document, $options);
        } else {
            $collection->insertOne($document);
        }
    }

    public function markNotMigrated()
    {
        $this->configuration->createMigrationCollection();
        $collection = $this->configuration->getCollection();
        $collection->deleteOne(['v' => $this->version]);
    }

    protected function updateStatisticsAfter()
    {
        foreach ($this->statistics as $name => $statistic) {
            try {
                $statistic->updateAfter();
                $name = $statistic->getCollection()->getCollectionName();
                $this->statistics[$name] = $statistic;
            } catch (\Exception $e) {
                $message = sprintf('     <info>Warning during %s: %s</info>',
                    $this->getExecutionState(),
                    $e->getMessage()
                );

                $this->outputWriter->write($message);
            }
        }
    }

    private function summarizeStatistics()
    {
        foreach ($this->statistics as $key => $statistic) {
            $this->outputWriter->write(sprintf("\n     Collection %s\n", $key));

            $line = '     ';
            $line .= 'metric ' . str_repeat(' ', 16 - strlen('metric'));
            $line .= 'before ' . str_repeat(' ', 20 - strlen('before'));
            $line .= 'after ' . str_repeat(' ', 20 - strlen('after'));
            $line .= 'difference ' . str_repeat(' ', 20 - strlen('difference'));

            $this->outputWriter->write($line . "\n     " . str_repeat('=', 80));
            $before = $statistic->getBefore();
            $after = $statistic->getAfter();

            foreach (Statistics::$metrics as $metric) {
                $valueBefore = isset($before[$metric]) ? $before[$metric] : 0;
                $valueAfter = isset($after[$metric]) ? $after[$metric] : 0;
                $difference = $valueAfter - $valueBefore;

                $nameMessage = $metric . str_repeat(' ', 16 - strlen($metric));
                $beforeMessage = $valueBefore . str_repeat(' ', 20 - strlen($valueBefore));
                $afterMessage = $valueAfter . str_repeat(' ', 20 - strlen($valueAfter));
                $differenceMessage = $difference . str_repeat(' ', 20 - strlen($difference));

                $line = sprintf(
                    '     %s %s %s %s',
                    $nameMessage,
                    $beforeMessage,
                    $afterMessage,
                    $differenceMessage
                );
                $this->outputWriter->write($line);
            }
        }
    }

    public function __toString()
    {
        return (string) $this->version;
    }

    /**
     * @return \AntiMattr\MongoDB\Migrations\AbstractMigration
     */
    protected function createMigration()
    {
        return new $this->class($this);
    }

    /**
     * @return UTCDateTime
     */
    protected function createMongoTimestamp()
    {
        return new UTCDateTime();
    }

    /**
     * @return \AntiMattr\MongoDB\Migrations\Collection\Statistics
     */
    protected function createStatistics()
    {
        return new Statistics();
    }
}
