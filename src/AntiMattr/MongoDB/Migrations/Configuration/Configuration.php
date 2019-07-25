<?php

/*
 * This file is part of the AntiMattr MongoDB Migrations Library, a library by Matthew Fitzgerald.
 *
 * (c) 2014 Matthew Fitzgerald
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AntiMattr\MongoDB\Migrations\Configuration;

use AntiMattr\MongoDB\Migrations\Exception\ConfigurationValidationException;
use AntiMattr\MongoDB\Migrations\Exception\DuplicateVersionException;
use AntiMattr\MongoDB\Migrations\Exception\UnknownVersionException;
use AntiMattr\MongoDB\Migrations\OutputWriter;
use AntiMattr\MongoDB\Migrations\Version;
use MongoDB\Client;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
class Configuration
{
    /**
     * @var \MongoDB\Collection
     */
    private $collection;

    /**
     * @var \MongoDB\Client
     */
    private $connection;

    /**
     * @var \MongoDB\Database
     */
    private $database;

    /**
     * The migration database name to track versions in.
     *
     * @var string
     */
    private $migrationsDatabaseName;

    /**
     * Flag for whether or not the migration collection has been created.
     *
     * @var bool
     */
    private $migrationCollectionCreated = false;

    /**
     * The migration collection name to track versions in.
     *
     * @var string
     */
    private $migrationsCollectionName = 'antimattr_migration_versions';

    /**
     * The path to a directory where new migration classes will be written.
     *
     * @var string
     */
    private $migrationsDirectory;

    /**
     * Namespace the migration classes live in.
     *
     * @var string
     */
    private $migrationsNamespace;

    /**
     * The path to a directory where mongo console scripts are.
     *
     * @var string
     */
    private $migrationsScriptDirectory;

    /**
     * Used by Console Commands and Output Writer.
     *
     * @var string
     */
    private $name;

    /**
     * @var \AntiMattr\MongoDB\Migrations\Version[]
     */
    protected $migrations = [];

    /**
     * @var \AntiMattr\MongoDB\Migrations\OutputWriter
     */
    private $outputWriter;

    /**
     * @var string
     */
    private $file;

    /**
     * @param \MongoDB\Client               $connection
     * @param \AntiMattr\MongoDB\Migrations\OutputWriter $outputWriter
     */
    public function __construct(Client $connection, OutputWriter $outputWriter = null)
    {
        $this->connection = $connection;
        if (null === $outputWriter) {
            $outputWriter = new OutputWriter();
        }
        $this->outputWriter = $outputWriter;
    }

    /**
     * Returns a timestamp version as a formatted date.
     *
     * @param string $version
     *
     * @return string The formatted version
     */
    public static function formatVersion($version)
    {
        return sprintf('%s-%s-%s %s:%s:%s',
            substr($version, 0, 4),
            substr($version, 4, 2),
            substr($version, 6, 2),
            substr($version, 8, 2),
            substr($version, 10, 2),
            substr($version, 12, 2)
        );
    }

    /**
     * Returns an array of available migration version numbers.
     *
     * @return array
     */
    public function getAvailableVersions()
    {
        $availableVersions = [];
        foreach ($this->migrations as $migration) {
            $availableVersions[] = $migration->getVersion();
        }

        return $availableVersions;
    }

    /**
     * @return \MongoDB\Collection
     */
    public function getCollection()
    {
        if (isset($this->collection)) {
            return $this->collection;
        }

        $this->collection = $this->getDatabase()->selectCollection($this->migrationsCollectionName);

        return $this->collection;
    }

    /**
     * @return \MongoDB\Client
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * @return \MongoDB\Database
     */
    public function getDatabase(): ?\MongoDB\Database
    {
        if (isset($this->database)) {
            return $this->database;
        }

        $this->database = $this->connection->selectDatabase($this->migrationsDatabaseName);

        return $this->database;
    }

    /**
     * Get the array of registered migration versions.
     *
     * @return Version[] $migrations
     */
    public function getMigrations()
    {
        return $this->migrations;
    }

    /**
     * @param string $databaseName
     */
    public function setMigrationsDatabaseName($databaseName)
    {
        $this->migrationsDatabaseName = $databaseName;

        return $this;
    }

    /**
     * @return string
     */
    public function getMigrationsDatabaseName()
    {
        return $this->migrationsDatabaseName;
    }

    /**
     * @param string $collectionName
     */
    public function setMigrationsCollectionName($collectionName)
    {
        $this->migrationsCollectionName = $collectionName;

        return $this;
    }

    /**
     * @return string
     */
    public function getMigrationsCollectionName()
    {
        return $this->migrationsCollectionName;
    }

    /**
     * @param string $migrationsDirectory
     */
    public function setMigrationsDirectory($migrationsDirectory)
    {
        $this->migrationsDirectory = $migrationsDirectory;

        return $this;
    }

    /**
     * @return string
     */
    public function getMigrationsDirectory()
    {
        return $this->migrationsDirectory;
    }

    /**
     * Set the migrations namespace.
     *
     * @param string $migrationsNamespace The migrations namespace
     */
    public function setMigrationsNamespace($migrationsNamespace)
    {
        $this->migrationsNamespace = $migrationsNamespace;

        return $this;
    }

    /**
     * @return string $migrationsNamespace
     */
    public function getMigrationsNamespace()
    {
        return $this->migrationsNamespace;
    }

    /**
     * @param string $scriptsDirectory
     */
    public function setMigrationsScriptDirectory($scriptsDirectory)
    {
        $this->migrationsScriptDirectory = $scriptsDirectory;

        return $this;
    }

    /**
     * @return string
     */
    public function getMigrationsScriptDirectory()
    {
        return $this->migrationsScriptDirectory;
    }

    /**
     * @param string $file
     */
    public function setFile($file)
    {
        $this->file = $file;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getFile(): ?string
    {
        return $this->file;
    }

    /**
     * Returns all migrated versions from the versions collection, in an array.
     *
     * @return \AntiMattr\MongoDB\Migrations\Version[]
     */
    public function getMigratedVersions()
    {
        $this->createMigrationCollection();

        $cursor = $this->getCollection()->find();
        $versions = [];
        foreach ($cursor as $record) {
            $versions[] = $record['v'];
        }

        return $versions;
    }

    /**
     * Returns the time a migration occurred.
     *
     * @param string $version
     *
     * @return int
     *
     * @throws AntiMattr\MongoDB\Migrations\Exception\UnknownVersionException Throws exception if migration version does not exist
     * @throws DomainException                                                If more than one version exists
     */
    public function getMigratedTimestamp($version): int
    {
        $this->createMigrationCollection();

        $cursor = $this->getCollection()->find(
            ['v' => $version]
        );

        $result = $cursor->toArray();
        if (!count($result)) {
            throw new UnknownVersionException($version);
        }

        if (count($result) > 1) {
            throw new \DomainException(
                'Unexpected duplicate version records in the database'
            );
        }

        $returnVersion = $result[0];

        // Convert to normalised timestamp
        $ts = new Timestamp($returnVersion['t']);

        return $ts->getTimestamp();
    }

    /**
     * Return all migrated versions from versions collection that have migration files deleted.
     *
     * @return array
     */
    public function getUnavailableMigratedVersions()
    {
        return array_diff($this->getMigratedVersions(), $this->getAvailableVersions());
    }

    /**
     * @param string $name
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string $name
     */
    public function getName()
    {
        return ($this->name) ?: 'Database Migrations';
    }

    /**
     * @return int
     */
    public function getNumberOfAvailableMigrations()
    {
        return count($this->migrations);
    }

    /**
     * @return int
     */
    public function getNumberOfExecutedMigrations()
    {
        $this->createMigrationCollection();

        return $this->getCollection()->countDocuments();
    }

    /**
     * @return \AntiMattr\MongoDB\Migrations\OutputWriter
     */
    public function getOutputWriter()
    {
        return $this->outputWriter;
    }

    /**
     * Register a single migration version to be executed by a AbstractMigration
     * class.
     *
     * @param string $version The version of the migration in the format YYYYMMDDHHMMSS
     * @param string $class   The migration class to execute for the version
     *
     * @return Version
     *
     * @throws AntiMattr\MongoDB\Migrations\Exception\DuplicateVersionException
     */
    public function registerMigration($version, $class)
    {
        $version = (string) $version;
        $class = (string) $class;
        if (isset($this->migrations[$version])) {
            $message = sprintf(
                'Migration version %s already registered with class %s',
                $version,
                get_class($this->migrations[$version])
            );
            throw new DuplicateVersionException($message);
        }
        $version = new Version($this, $version, $class);
        $this->migrations[$version->getVersion()] = $version;
        ksort($this->migrations);

        return $version;
    }

    /**
     * Register an array of migrations. Each key of the array is the version and
     * the value is the migration class name.
     *
     *
     * @param array $migrations
     *
     * @return Version[]
     */
    public function registerMigrations(array $migrations)
    {
        $versions = [];
        foreach ($migrations as $version => $class) {
            $versions[] = $this->registerMigration($version, $class);
        }

        return $versions;
    }

    /**
     * Register migrations from a given directory. Recursively finds all files
     * with the pattern VersionYYYYMMDDHHMMSS.php as the filename and registers
     * them as migrations.
     *
     * @param string $path The root directory to where some migration classes live
     *
     * @return Version[] The array of migrations registered
     */
    public function registerMigrationsFromDirectory($path)
    {
        $path = realpath($path);
        $path = rtrim($path, '/');
        $files = glob($path . '/Version*.php');
        $versions = [];
        if ($files) {
            foreach ($files as $file) {
                require_once $file;
                $info = pathinfo($file);
                $version = substr($info['filename'], 7);
                $class = $this->migrationsNamespace . '\\' . $info['filename'];
                $versions[] = $this->registerMigration($version, $class);
            }
        }

        return $versions;
    }

    /**
     * Returns the Version instance for a given version in the format YYYYMMDDHHMMSS.
     *
     * @param string $version The version string in the format YYYYMMDDHHMMSS
     *
     * @return \AntiMattr\MongoDB\Migrations\Version
     *
     * @throws AntiMattr\MongoDB\Migrations\Exception\UnknownVersionException Throws exception if migration version does not exist
     */
    public function getVersion($version)
    {
        if (!isset($this->migrations[$version])) {
            throw new UnknownVersionException($version);
        }

        return $this->migrations[$version];
    }

    /**
     * Check if a version exists.
     *
     * @param string $version
     *
     * @return bool
     */
    public function hasVersion($version)
    {
        return isset($this->migrations[$version]);
    }

    /**
     * Check if a version has been migrated or not yet.
     *
     * @param \AntiMattr\MongoDB\Migrations\Version $version
     *
     * @return bool
     */
    public function hasVersionMigrated(Version $version)
    {
        $this->createMigrationCollection();

        $record = $this->getCollection()->findOne(['v' => $version->getVersion()]);

        return null !== $record;
    }

    /**
     * @return string
     */
    public function getCurrentVersion()
    {
        $this->createMigrationCollection();

        $migratedVersions = [];
        if (!empty($this->migrations)) {
            foreach ($this->migrations as $migration) {
                $migratedVersions[] = $migration->getVersion();
            }
        }

        $cursor = $this->getCollection()
            ->find(
                ['v' => ['$in' => $migratedVersions]],
                ['sort' => ['v' => -1], 'limit' => 1]
            );

        $versions = $cursor->toArray();
        if (0 === \count($versions)) {
            return '0';
        }

        $version = $versions[0];
        return $version['v'];
    }

    /**
     * Returns the latest available migration version.
     *
     * @return string The version string in the format YYYYMMDDHHMMSS
     */
    public function getLatestVersion()
    {
        $versions = array_keys($this->migrations);
        $latest = end($versions);

        return false !== $latest ? (string) $latest : '0';
    }

    /**
     * Create the migration collection to track migrations with.
     *
     * @return bool Whether or not the collection was created
     */
    public function createMigrationCollection()
    {
        $this->validate();

        if (true !== $this->migrationCollectionCreated) {
            $collection = $this->getCollection();
            $collection->createIndex(['v' => -1], ['name' => 'version', 'unique' => true]);
            $this->migrationCollectionCreated = true;
        }

        return true;
    }

    /**
     * Returns the array of migrations to executed based on the given direction
     * and target version number.
     *
     * @param string $direction The direction we are migrating
     * @param string $to        The version to migrate to
     *
     * @return Version[] $migrations   The array of migrations we can execute
     */
    public function getMigrationsToExecute($direction, $to)
    {
        if ('down' === $direction) {
            if (count($this->migrations)) {
                $allVersions = array_reverse(array_keys($this->migrations));
                $classes = array_reverse(array_values($this->migrations));
                $allVersions = array_combine($allVersions, $classes);
            } else {
                $allVersions = [];
            }
        } else {
            $allVersions = $this->migrations;
        }
        $versions = [];
        $migrated = $this->getMigratedVersions();
        foreach ($allVersions as $version) {
            if ($this->shouldExecuteMigration($direction, $version, $to, $migrated)) {
                $versions[$version->getVersion()] = $version;
            }
        }

        return $versions;
    }

    /**
     * Check if we should execute a migration for a given direction and target
     * migration version.
     *
     * @param string  $direction The direction we are migrating
     * @param Version $version   The Version instance to check
     * @param string  $to        The version we are migrating to
     * @param array   $migrated  Migrated versions array
     *
     * @return bool
     */
    private function shouldExecuteMigration($direction, Version $version, $to, $migrated)
    {
        if ('down' === $direction) {
            if (!in_array($version->getVersion(), $migrated)) {
                return false;
            }

            return $version->getVersion() > $to;
        }

        if ('up' === $direction) {
            if (in_array($version->getVersion(), $migrated)) {
                return false;
            }

            return $version->getVersion() <= $to;
        }
    }

    /**
     * Validation that this instance has all the required properties configured.
     *
     * @throws AntiMattr\MongoDB\Migrations\Exception\ConfigurationValidationException
     */
    public function validate()
    {
        if (!$this->migrationsDatabaseName) {
            $message = 'Migrations Database Name must be configured in order to use AntiMattr migrations.';
            throw new ConfigurationValidationException($message);
        }
        if (!$this->migrationsNamespace) {
            $message = 'Migrations namespace must be configured in order to use AntiMattr migrations.';
            throw new ConfigurationValidationException($message);
        }
        if (!$this->migrationsDirectory) {
            $message = 'Migrations directory must be configured in order to use AntiMattr migrations.';
            throw new ConfigurationValidationException($message);
        }
    }

    /**
     * @return array
     */
    public function getDetailsMap()
    {
        // Executed migration count
        $executedMigrations = $this->getMigratedVersions();
        $numExecutedMigrations = count($executedMigrations);

        // Available migration count
        $availableMigrations = $this->getAvailableVersions();
        $numAvailableMigrations = count($availableMigrations);

        // Executed Unavailable migration count
        $numExecutedUnavailableMigrations = count($this->getUnavailableMigratedVersions());

        // New migration count
        $numNewMigrations = $numAvailableMigrations - ($numExecutedMigrations - $numExecutedUnavailableMigrations);

        return [
            'name' => $this->getName(),
            'database_driver' => 'MongoDB',
            'migrations_database_name' => $this->getMigrationsDatabaseName(),
            'migrations_collection_name' => $this->getMigrationsCollectionName(),
            'migrations_namespace' => $this->getMigrationsNamespace(),
            'migrations_directory' => $this->getMigrationsDirectory(),
            'current_version' => $this->getCurrentVersion(),
            'latest_version' => $this->getLatestVersion(),
            'num_executed_migrations' => $numExecutedMigrations,
            'num_executed_unavailable_migrations' => $numExecutedUnavailableMigrations,
            'num_available_migrations' => $numAvailableMigrations,
            'num_new_migrations' => $numNewMigrations,
        ];
    }
}
