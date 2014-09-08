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
use AntiMattr\MongoDB\Migrations\Exception\SkipException;
use Doctrine\MongoDB\Collection;
use Doctrine\MongoDB\Database;
use Exception;
use MongoTimestamp;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
class Version
{
    const STATE_NONE = 0;
    const STATE_PRE  = 1;
    const STATE_EXEC = 2;
    const STATE_POST = 3;

    /**
     * @var string
     */
    private $class;

    /**
     * @var AntiMattr\MongoDB\Migrations\Configuration\Configuration
     */
    private $configuration;

    /**
     * @var Doctrine\MongoDB\Connection
     */
    private $connection;

    /**
     * @var AntiMattr\MongoDB\Migrations\AbstractMigration
     */
    protected $migration;

    /**
     * @var AntiMattr\MongoDB\Migrations\OutputWriter
     */
    private $outputWriter;

    /**
     * The version in timestamp format (YYYYMMDDHHMMSS)
     *
     * @var int
     */
    private $version;

    /**
     * @var AntiMattr\MongoDB\Migrations\Collection\Statistics[]
     */
    private $statistics = array();

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
        $this->connection = $configuration->getConnection();
        $this->db = $configuration->getDatabase();
        $this->migration = $this->createMigration();
        $this->version = $version;
    }

    /**
     * @return AntiMattr\MongoDB\Migrations\Configuration\Configuration $configuration
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
     * @return AntiMattr\MongoDB\Migrations\AbstractMigration
     */
    public function getMigration()
    {
        return $this->migration;
    }

    /**
     * @return boolean
     */
    public function isMigrated()
    {
        return $this->configuration->hasVersionMigrated($this);
    }

    /**
     * Returns the time this migration version took to execute
     *
     * @return integer $time The time this migration version took to execute
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
     * @param Doctrine\MongoDB\Collection
     */
    public function analyze(Collection $collection)
    {
        $statistics = $this->createStatistics();
        $statistics->setCollection($collection);
        $name = $collection->getName();
        $this->statistics[$name] = $statistics;

        try {
            $statistics->updateBefore();
        } catch (\Exception $e) {
            $message = sprintf("     <info>Warning during %s: %s</info>",
                $this->getExecutionState(),
                $e->getMessage()
            );

            $this->outputWriter->write($message);
        }
    }

    /**
     * Execute this migration version up or down and and return the SQL.
     *
     * @param string $direction The direction to execute the migration.
     *
     * @throws \Exception when migration fails
     */
    public function execute($direction)
    {
        try {
            $start = microtime(true);

            $this->state = self::STATE_PRE;

            $this->migration->{'pre'.ucfirst($direction)}($this->db);

            if ($direction === 'up') {
                $this->outputWriter->write("\n".sprintf('  <info>++</info> migrating <comment>%s</comment>', $this->version)."\n");
            } else {
                $this->outputWriter->write("\n".sprintf('  <info>--</info> reverting <comment>%s</comment>', $this->version)."\n");
            }

            $this->state = self::STATE_EXEC;

            $this->migration->$direction($this->db);

            $this->updateStatisticsAfter();

            if ($direction === 'up') {
                $this->markMigrated();
            } else {
                $this->markNotMigrated();
            }

            $this->summarizeStatistics();

            $this->state = self::STATE_POST;
            $this->migration->{'post'.ucfirst($direction)}($this->db);

            $end = microtime(true);
            $this->time = round($end - $start, 2);
            if ($direction === 'up') {
                $this->outputWriter->write(sprintf("\n  <info>++</info> migrated (%ss)", $this->time));
            } else {
                $this->outputWriter->write(sprintf("\n  <info>--</info> reverted (%ss)", $this->time));
            }

            $this->state = self::STATE_NONE;

        } catch (SkipException $e) {

            // now mark it as migrated
            if ($direction === 'up') {
                $this->markMigrated();
            } else {
                $this->markNotMigrated();
            }

            $this->outputWriter->write(sprintf("\n  <info>SS</info> skipped (Reason: %s)",  $e->getMessage()));

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
     * @param Doctrine\MongoDB\Database
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

        $path = realpath($scripts.'/'.$file);
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

        $result = $db->command(array('$eval' => $js, 'nolock' => true));

        if (isset($result['errmsg'])) {
            throw new \Exception($result['errmsg'], isset($result['errno']) ? $result['errno'] : null);
        }

        return $result;
    }

    public function markMigrated()
    {
        $this->configuration->createMigrationCollection();
        $collection = $this->configuration->getCollection();

        $document = array("v" => $this->version, "t" => $this->createMongoTimestamp());
        $collection->insert($document);
    }

    public function markNotMigrated()
    {
        $this->configuration->createMigrationCollection();
        $collection = $this->configuration->getCollection();
        $collection->remove(array("v" => $this->version));
    }

    protected function updateStatisticsAfter()
    {
        foreach ($this->statistics as $name => $statistic) {
            try {
                $statistic->updateAfter();
                $name = $statistic->getCollection()->getName();
                $this->statistics[$name] = $statistic;
            } catch (\Exception $e) {
                $message = sprintf("     <info>Warning during %s: %s</info>",
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

            $line  = '     ';
            $line .= 'metric '.str_repeat(' ', 16 - strlen('metric'));
            $line .= 'before '.str_repeat(' ', 20 - strlen('before'));
            $line .= 'after '.str_repeat(' ', 20 - strlen('after'));
            $line .= 'difference '.str_repeat(' ', 20 - strlen('difference'));

            $this->outputWriter->write($line."\n     ".str_repeat('=', 80));
            $before = $statistic->getBefore();
            $after = $statistic->getAfter();

            foreach (Statistics::$metrics as $metric) {
                $valueBefore = isset($before[$metric]) ? $before[$metric] : 0;
                $valueAfter = isset($after[$metric]) ? $after[$metric] : 0;
                $difference = $valueAfter - $valueBefore;

                $nameMessage = $metric.str_repeat(' ', 16 - strlen($metric));
                $beforeMessage = $valueBefore.str_repeat(' ', 20 - strlen($valueBefore));
                $afterMessage = $valueAfter.str_repeat(' ', 20 - strlen($valueAfter));
                $differenceMessage = $difference.str_repeat(' ', 20 - strlen($difference));

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
        return $this->version;
    }

    /**
     * @return AntiMattr\MongoDB\Migrations\AbstractMigration
     */
    protected function createMigration()
    {
        return new $this->class($this);
    }

    /**
     * @return MongoTimestamp
     */
    protected function createMongoTimestamp()
    {
        return new MongoTimestamp();
    }

    /**
     * @return AntiMattr\MongoDB\Migrations\Collection\Statistics
     */
    protected function createStatistics()
    {
        return new Statistics();
    }
}
