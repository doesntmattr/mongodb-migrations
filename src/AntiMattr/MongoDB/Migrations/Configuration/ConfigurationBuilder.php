<?php

declare(strict_types=1);

/*
 * This file is part of the AntiMattr MongoDB Migrations Library, a library by Matthew Fitzgerald.
 *
 * (c) 2014 Matthew Fitzgerald
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AntiMattr\MongoDB\Migrations\Configuration;

use AntiMattr\MongoDB\Migrations\OutputWriter;
use MongoDB\Client;
use Symfony\Component\Yaml\Yaml;

/**
 * @author Douglas Reith <douglas@reith.com.au>
 */
class ConfigurationBuilder
{
    /**
     * @var \MongoDB\Client
     */
    private $connection;

    /**
     * @var OutputWriter
     */
    private $outputWriter;

    /**
     * @var array
     */
    private $configParams;

    /**
     * @var string
     */
    private $configFile;

    private function __construct()
    {
        $this->configParams = [
            'name' => null,
            'database' => null,
            'collection_name' => null,
            'migrations_namespace' => null,
            'migrations_directory' => null,
            'migrations_script_directory' => null,
            'migrations' => [],
        ];
    }

    public static function create(): ConfigurationBuilder
    {
        return new static();
    }

    public function setConnection(Client $connection): ConfigurationBuilder
    {
        $this->connection = $connection;

        return $this;
    }

    public function setOutputWriter(OutputWriter $outputWriter): ConfigurationBuilder
    {
        $this->outputWriter = $outputWriter;

        return $this;
    }

    public function setOnDiskConfiguration(?string $configFile = null): ConfigurationBuilder
    {
        $this->configFile = $configFile;

        if ($this->configFile) {
            if (file_exists($path = getcwd() . '/' . $this->configFile)) {
                $this->configFile = $path;
            }

            if (!file_exists($this->configFile)) {
                throw new \InvalidArgumentException('The specified config file is not a valid file.');
            }

            $info = pathinfo($this->configFile);

            $fileExt = strtolower($info['extension']);

            switch ($fileExt) {
                case 'xml':
                    $diskConfig = $this->loadXmlFile($this->configFile);
                    break;

                case 'yml':
                case 'yaml':
                    $diskConfig = Yaml::parse(file_get_contents($this->configFile));
                    break;

                default:
                    throw new \InvalidArgumentException(sprintf('The specified config file should end in .xml or .yml/.yaml. Unrecognized extension [%s]', $fileExt));
            }

            $this->configParams = array_merge($this->configParams, $diskConfig);
        }

        return $this;
    }

    public function build(): Configuration
    {
        $config = new Configuration($this->connection, $this->outputWriter);

        $config->setName($this->configParams['name'])
            ->setFile($this->configFile)
            ->setMigrationsDatabaseName((string) $this->configParams['database'])
            ->setMigrationsCollectionName($this->configParams['collection_name'])
            ->setMigrationsNamespace($this->configParams['migrations_namespace'])
        ;

        if (!empty($this->configParams['migrations_directory'])) {
            $migrationsDirectory = $this->getDirectoryRelativeToFile(
                $this->configFile,
                $this->configParams['migrations_directory']
            );

            $config->setMigrationsDirectory($migrationsDirectory)
                ->registerMigrationsFromDirectory($migrationsDirectory);
        }

        if (!empty($this->configParams['migrations_script_directory'])) {
            $scriptsDirectory = $this->getDirectoryRelativeToFile(
                $this->configFile,
                $this->configParams['migrations_script_directory']
            );

            $config->setMigrationsScriptDirectory($scriptsDirectory);
        }

        foreach ($this->configParams['migrations'] as $migration) {
            $config->registerMigration(
                $migration['version'],
                $migration['class']
            );
        }

        return $config;
    }

    private function loadXmlFile(string $configFile): array
    {
        $xml = simplexml_load_file($configFile);
        $configArr = [];

        if (isset($xml->name)) {
            $configArr['name'] = (string) $xml->name;
        }

        if (isset($xml->database['name'])) {
            $configArr['database'] = (string) $xml->database['name'];
        }

        if (isset($xml->collection['name'])) {
            $configArr['collection_name'] = (string) $xml->collection['name'];
        }

        if (isset($xml->{'migrations-namespace'})) {
            $configArr['migrations_namespace'] = (string) $xml->{'migrations-namespace'};
        }

        if (isset($xml->{'migrations-directory'})) {
            $configArr['migrations_directory'] = (string) $xml->{'migrations-directory'};
        }

        if (isset($xml->{'migrations-script-directory'})) {
            $configArr['migrations_script_directory'] = (string) $xml->{'migrations-script-directory'};
        }

        if (isset($xml->migrations->migration)) {
            foreach ($xml->migrations->migration as $migration) {
                $configArr['migrations'][] = [
                    'version' => $migration['version'],
                    'class' => $migration['class'],
                ];
            }
        }

        return $configArr;
    }

    /**
     * Get the path to the directory relative to the config file.
     */
    protected function getDirectoryRelativeToFile(string $configFile, ?string $directory = null): ?string
    {
        if (!$directory) {
            return null;
        }

        $path = realpath(dirname($configFile) . '/' . $directory);

        if (false !== $path) {
            return $path;
        }

        return $directory;
    }
}
