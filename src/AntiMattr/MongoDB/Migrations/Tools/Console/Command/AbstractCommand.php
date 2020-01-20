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

use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use AntiMattr\MongoDB\Migrations\Configuration\ConfigurationBuilder;
use AntiMattr\MongoDB\Migrations\OutputWriter;
use MongoDB\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
abstract class AbstractCommand extends Command
{
    /**
     * @var \AntiMattr\MongoDB\Migrations\Configuration\Configuration
     */
    private $configuration;

    /**
     * configure.
     */
    protected function configure()
    {
        $this->addOption(
            'configuration', null, InputOption::VALUE_OPTIONAL, 'The path to a migrations configuration file.'
        );
        $this->addOption(
            'db-configuration', null, InputOption::VALUE_OPTIONAL, 'The path to a database connection configuration file.'
        );
    }

    protected function outputHeader(Configuration $configuration, OutputInterface $output)
    {
        $name = $configuration->getName();
        $name = $name ? $name : 'AntiMattr Database Migrations';
        $name = str_repeat(' ', 20) . $name . str_repeat(' ', 20);
        $output->writeln('<question>' . str_repeat(' ', strlen($name)) . '</question>');
        $output->writeln('<question>' . $name . '</question>');
        $output->writeln('<question>' . str_repeat(' ', strlen($name)) . '</question>');
        $output->writeln('');
    }

    /**
     * @param \AntiMattr\MongoDB\Migrations\Configuration\Configuration
     */
    public function setMigrationConfiguration(Configuration $config)
    {
        $this->configuration = $config;
    }

    protected function getMigrationConfiguration(
        InputInterface $input,
        OutputInterface $output
    ): Configuration {
        if (!$this->configuration) {
            $conn = $this->getDatabaseConnection($input);

            $outputWriter = new OutputWriter(function ($message) use ($output) {
                return $output->writeln($message);
            });

            $migrationsConfigFile = $input->getOption('configuration');

            $this->configuration = ConfigurationBuilder::create()
                ->setConnection($conn)
                ->setOutputWriter($outputWriter)
                ->setOnDiskConfiguration($migrationsConfigFile)
                ->build();
        }

        return $this->configuration;
    }

    protected function getDatabaseConnection(InputInterface $input): Client
    {
        // Default to document manager helper set
        if ($this->getApplication()->getHelperSet()->has('dm')) {
            return $this->getHelper('dm')
                ->getDocumentManager()
                ->getClient();
        }

        // PHP array file
        $dbConfiguration = $input->getOption('db-configuration');

        if (!$dbConfiguration) {
            throw new \InvalidArgumentException('You have to specify a --db-configuration file or pass a Database Connection as a dependency to the Migrations.');
        }

        if (!file_exists($dbConfiguration)) {
            throw new \InvalidArgumentException('The specified connection file is not a valid file.');
        }

        $dbConfigArr = include $dbConfiguration;

        if (!is_array($dbConfigArr)) {
            throw new \InvalidArgumentException('The connection file has to return an array with database configuration parameters.');
        }

        return $this->createConnection($dbConfigArr);
    }

    /**
     * @param array $params
     *
     * @return \MongoDB\Client
     */
    protected function createConnection($params)
    {
        $credentials = '';
        if (isset($params['password'])) {
            $credentials = ':' . $params['password'];
        }
        if (isset($params['user'])) {
            $credentials = $params['user'] . $credentials . '@';
        }

        $database = '';
        if (isset($params['dbname'])) {
            $database = '/' . $params['dbname'];
        }

        $server = sprintf(
            'mongodb://%s%s:%s%s',
            $credentials,
            (isset($params['host']) ? $params['host'] : 'localhost'),
            (isset($params['port']) ? $params['port'] : '27017'),
            $database
        );

        $options = [];
        if (!empty($params['options'])) {
            $options = $params['options'];
        }

        return new Client($server, $options);
    }
}
