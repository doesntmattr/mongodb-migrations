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
use AntiMattr\MongoDB\Migrations\OutputWriter;
use Doctrine\MongoDB\Connection;
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
     * @var AntiMattr\MongoDB\Migrations\Configuration\Configuration
     */
    private $configuration;

    protected function configure()
    {
        $this->addOption('configuration', null, InputOption::VALUE_OPTIONAL, 'The path to a migrations configuration file.');
        $this->addOption('db-configuration', null, InputOption::VALUE_OPTIONAL, 'The path to a database connection configuration file.');
    }

    /**
     * @param AntiMattr\MongoDB\Migrations\Configuration\Configuration
     * @param Symfony\Component\Console\Output\OutputInterface
     */
    protected function outputHeader(Configuration $configuration, OutputInterface $output)
    {
        $name = $configuration->getName();
        $name = $name ? $name : 'AntiMattr Database Migrations';
        $name = str_repeat(' ', 20).$name.str_repeat(' ', 20);
        $output->writeln('<question>'.str_repeat(' ', strlen($name)).'</question>');
        $output->writeln('<question>'.$name.'</question>');
        $output->writeln('<question>'.str_repeat(' ', strlen($name)).'</question>');
        $output->writeln('');
    }

    /**
     * @param AntiMattr\MongoDB\Migrations\Configuration\Configuration
     */
    public function setMigrationConfiguration(Configuration $config)
    {
        $this->configuration = $config;
    }

    /**
     * @param Symfony\Component\Console\Output\InputInterface  $input
     * @param Symfony\Component\Console\Output\OutputInterface $output
     *
     * @return AntiMattr\MongoDB\Migrations\Configuration\Configuration
     */
    protected function getMigrationConfiguration(InputInterface $input, OutputInterface $output)
    {
        if (!$this->configuration) {
            $outputWriter = new OutputWriter(function ($message) use ($output) {
                return $output->writeln($message);
            });

            if ($this->getApplication()->getHelperSet()->has('dm')) {
                // Doctrine\MongoDB\Connection
                $conn = $this->getHelper('dm')->getDocumentManager()->getConnection();
            } elseif ($input->getOption('db-configuration')) {
                if (!file_exists($input->getOption('db-configuration'))) {
                    throw new \InvalidArgumentException("The specified connection file is not a valid file.");
                }

                $params = include $input->getOption('db-configuration');
                if (!is_array($params)) {
                    throw new \InvalidArgumentException('The connection file has to return an array with database configuration parameters.');
                }
                $conn = $this->createConnection($params);
            } else {
                throw new \InvalidArgumentException('You have to specify a --db-configuration file or pass a Database Connection as a dependency to the Migrations.');
            }

            if ($input->getOption('configuration')) {
                $info = pathinfo($input->getOption('configuration'));
                $namespace = 'AntiMattr\MongoDB\Migrations\Configuration';
                $class = $info['extension'] === 'xml' ? 'XmlConfiguration' : 'YamlConfiguration';
                $class = sprintf('%s\%s', $namespace, $class);
                $configuration = new $class($conn, $outputWriter);
                $configuration->load($input->getOption('configuration'));
            } else {
                $configuration = new Configuration($conn, $outputWriter);
            }
            $this->configuration = $configuration;
        }

        return $this->configuration;
    }

    /**
     * @params array $params
     *
     * @return Doctrine\MongoDB\Connection
     */
    protected function createConnection($params)
    {
        $credentials = '';
        if (isset($params['password'])) {
            $credentials = ':'.$params['password'];
        }
        if (isset($params['user'])) {
            $credentials = $params['user'].$credentials.'@';
        }

        $database = '';
        if (isset($params['dbname'])) {
            $database = '/'.$params['dbname'];
        }

        $server = sprintf(
            "mongodb://%s%s:%s",
            $credentials,
            (isset($params['host']) ? $params['host'] : 'localhost'),
            (isset($params['port']) ? $params['port'] : '27017'),
            $database
        );

        $options = array();
        if (!empty($params['options'])) {
            $options = $params['options'];
        }

        return new Connection($server, $options);
    }
}
