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

use AntiMattr\MongoDB\Migrations\Exception\AbortException;
use AntiMattr\MongoDB\Migrations\Exception\IrreversibleException;
use AntiMattr\MongoDB\Migrations\Exception\SkipException;
use \MongoDB\Collection;
use \MongoDB\Database;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
abstract class AbstractMigration
{
    /**
     * @var \AntiMattr\MongoDB\Migrations\Configuration\Configuration
     */
    private $configuration;

    /**
     * @var \AntiMattr\MongoDB\Migrations\OutputWriter
     */
    private $outputWriter;

    /**
     * @var \AntiMattr\MongoDB\Migrations\Version
     */
    protected $version;

    public function __construct(Version $version)
    {
        $this->configuration = $version->getConfiguration();
        $this->outputWriter = $this->configuration->getOutputWriter();
        $this->version = $version;
    }

    /**
     * Get custom migration description.
     *
     * @return string
     */
    abstract public function getDescription();

    abstract public function up(Database $db);

    abstract public function down(Database $db);

    /**
     * @param \MongoDB\Collection
     */
    protected function analyze(Collection $collection)
    {
        $this->version->analyze($collection);
    }

    /**
     * @param \MongoDB\Database
     * @param string $filename
     */
    protected function executeScript(Database $db, $filename)
    {
        return $this->version->executeScript($db, $filename);
    }

    /**
     * @param string
     */
    protected function write($message)
    {
        $this->outputWriter->write($message);
    }

    /**
     * @param string $message
     *
     * @throws AntiMattr\MongoDB\Migrations\Exception\IrreversibleException
     */
    protected function throwIrreversibleMigrationException($message = null)
    {
        if (null === $message) {
            $message = 'This migration is irreversible and cannot be reverted.';
        }
        throw new IrreversibleException($message);
    }

    /**
     * Print a warning message if the condition evalutes to TRUE.
     *
     * @param bool   $condition
     * @param string $message
     */
    public function warnIf($condition, $message = '')
    {
        $message = (strlen($message)) ? $message : 'Unknown Reason';

        if (true === $condition) {
            $this->outputWriter->write('    <warning>Warning during ' . $this->version->getExecutionState() . ': ' . $message . '</warning>');
        }
    }

    /**
     * Abort the migration if the condition evalutes to TRUE.
     *
     * @param bool   $condition
     * @param string $message
     *
     * @throws AntiMattr\MongoDB\Migrations\Exception\AbortException
     */
    public function abortIf($condition, $message = '')
    {
        $message = (strlen($message)) ? $message : 'Unknown Reason';

        if (true === $condition) {
            throw new AbortException($message);
        }
    }

    /**
     * Skip this migration (but not the next ones) if condition evalutes to TRUE.
     *
     * @param bool   $condition
     * @param string $message
     *
     * @throws AntiMattr\MongoDB\Migrations\Exception\SkipException
     */
    public function skipIf($condition, $message = '')
    {
        $message = (strlen($message)) ? $message : 'Unknown Reason';

        if (true === $condition) {
            throw new SkipException($message);
        }
    }

    public function preUp(Database $db)
    {
    }

    public function postUp(Database $db)
    {
    }

    public function preDown(Database $db)
    {
    }

    public function postDown(Database $db)
    {
    }
}
