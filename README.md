antimattr-mongodb-migrations
============================

The AntiMattr MongoDB Migration library provides managed migration support for MongoDB.

Are you familiar with [Doctrine Migrations](https://github.com/doctrine/migrations)?

This library intentionally parallels the structure and features provided.

Installation
============

Use composer to install

```bash
composer install
```

Features
========

Features - Configuration
------------------------

Similar to [Doctrine Migrations](https://github.com/doctrine/migrations), configurations are separated into 2 files

 * Connection configuration (php)
 * Migration configuration (xml or yaml)

Example Connection configuration "test_antimattr_mongodb.php"

```php
/**
 * @link http://php.net/manual/en/mongoclient.construct.php
 */
return array(
    'host' => 'localhost', // default is localhost
    'port' => '27017', // default is 27017
    'dbname' => null, // optional, if authentication DB is required
    'user' => null, // optional, if authentication is required
    'password' => null, // optional, if authentication is required
    'options' => array(
        'connect' => true // recommended
    )
);
```

XML or YAML Migration Configurations are supported

Example XML "test_antimattr_mongodb.xml"

```xml
<?xml version="1.0" encoding="UTF-8"?>
<antimattr-migrations xmlns="http://doctrine-project.org/schemas/migrations/configuration"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://doctrine-project.org/schemas/migrations/configuration
                    http://doctrine-project.org/schemas/migrations/configuration.xsd">

    <name>AntiMattr Sandbox Migrations</name>
    <migrations-namespace>AntiMattrMigrationsTest</migrations-namespace>
    <database name="test_antimattr_migrations" />
    <collection name="antimattr_migration_versions_test" />
    <migrations-directory>/path/to/migrations/classes/AntiMattrMigrations</migrations-directory>
    <!-- Script Directory Optional -->
    <migrations-script-directory>/path/to/migrations/script_directory</migrations-script-directory>

</antimattr-migrations>
```

Example YAML "test_antimattr_mongodb.yml"

```yaml
---
name: AntiMattr Sandbox Migrations
migrations_namespace: AntiMattrMigrationsTest
database: test_antimattr_migrations
collection_name: antimattr_migration_versions_test
migrations_directory: /path/to/migrations/classes/AntiMattrMigrations
migrations_script_directory: /path/to/migrations/script_directory # optional
```

Features - Console Command Support
----------------------------------

There is an example Console Application in the demo directory

This is how to register the commands in your application

```php
require '../../vendor/autoload.php';

error_reporting(E_ALL & ~E_NOTICE);

use AntiMattr\MongoDB\Migrations\Tools\Console\Command as AntiMattr;
use Symfony\Component\Console\Application;

$application = new Application();
$application->addCommands(array(
    new AntiMattr\ExecuteCommand(),
    new AntiMattr\GenerateCommand(),
    new AntiMattr\MigrateCommand(),
    new AntiMattr\StatusCommand(),
    new AntiMattr\VersionCommand()
));
$application->run();
```

Notice the console is executable

```bash
> cd demo/ConsoleApplication/
> ./console
Console Tool

Usage:
  [options] command [arguments]

Options:
  --help           -h Display this help message.
  --quiet          -q Do not output any message.
  --verbose        -v|vv|vvv Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug
  --version        -V Display this application version.
  --ansi              Force ANSI output.
  --no-ansi           Disable ANSI output.
  --no-interaction -n Do not ask any interactive question.

Available commands:
  help                          Displays help for a command
  list                          Lists commands
mongodb
  mongodb:migrations:execute    Execute a single migration version up or down manually.
  mongodb:migrations:generate   Generate a blank migration class.
  mongodb:migrations:migrate    Execute a migration to a specified version or the latest available version.
  mongodb:migrations:status     View the status of a set of migrations.
  mongodb:migrations:version    Manually add and delete migration versions from the version table.
```

Features - Generate a New Migration
-----------------------------------

```bash
> ./console mongodb:migrations:generate --db-configuration=config/test_antimattr_mongodb.php --configuration=config/test_antimattr_mongodb.yml
Generated new migration class to "Example/Migrations/TestAntiMattr/MongoDB/Version20140822185742.php"
```

Features - Status of Migrations
-------------------------------

```bash
> ./console mongodb:migrations:status --db-configuration=config/test_antimattr_mongodb.php --configuration=config/test_antimattr_mongodb.yml

 == Configuration

    >> Name:                                AntiMattr Example Migrations
    >> Database Driver:                     MongoDB
    >> Database Name:                       test_antimattr_migrations
    >> Configuration Source:                demo/ConsoleApplication/config/test_antimattr_mongodb.yml
    >> Version Collection Name:             migration_versions
    >> Migrations Namespace:                Example\Migrations\TestAntiMattr\MongoDB
    >> Migrations Directory:                Example/Migrations/TestAntiMattr/MongoDB
    >> Current Version:                     0
    >> Latest Version:                      2014-08-22 18:57:44 (20140822185744)
    >> Executed Migrations:                 0
    >> Executed Unavailable Migrations:     0
    >> Available Migrations:                3
    >> New Migrations:                      3
```

Features - Migrate all Migrations
---------------------------------

This is what you will execute during your deployment process.

```bash
./console mongodb:migrations:migrate --db-configuration=config/test_antimattr_mongodb.php --configuration=config/test_antimattr_mongodb.yml 
                                                                    
                    AntiMattr Example Migrations                    
                                                                    

WARNING! You are about to execute a database migration that could result in data lost. Are you sure you wish to continue? (y/n)y
Migrating up to 20140822185744 from 0

  ++ migrating 20140822185742


     Collection test_a

     metric           before               after                difference           
     ================================================================================
     count            100                  100                  0                   
     size             20452                20452                0                   
     avgObjSize       204.52               204.52               0                   
     storageSize      61440                61440                0                   
     numExtents       2                    2                    0                   
     nindexes         1                    2                    1                   
     lastExtentSize   49152                49152                0                   
     paddingFactor    1                    1                    0                   
     totalIndexSize   8176                 16352                8176                

  ++ migrated (0.03s)

  ++ migrating 20140822185743


  ++ migrated (0s)

  ++ migrating 20140822185744


  ++ migrated (0s)

  ------------------------

  ++ finished in 0.03
  ++ 3 migrations executed
```

Features - Execute a Single Migration
-------------------------------------

```bash
./console mongodb:migrations:execute --db-configuration=config/test_antimattr_mongodb.php --configuration=config/test_antimattr_mongodb.yml 20140822185742
WARNING! You are about to execute a database migration that could result in data lost. Are you sure you wish to continue? (y/n)y

  ++ migrating 20140822185742


     Collection test_a

     metric           before               after                difference           
     ================================================================================
     count            100                  100                  0                   
     size             20620                20620                0                   
     avgObjSize       206.2                206.2                0                   
     storageSize      61440                61440                0                   
     numExtents       2                    2                    0                   
     nindexes         1                    2                    1                   
     lastExtentSize   49152                49152                0                   
     paddingFactor    1                    1                    0                   
     totalIndexSize   8176                 16352                8176                

  ++ migrated (0.02s)
```

Features - Version Up or Down
-----------------------------

Is your migration history out of sync for some reason? You can manually add or remove a record from the history without running the underlying migration.

You can delete

```bash
./console mongodb:migrations:version --db-configuration=config/test_antimattr_mongodb.php --configuration=config/test_antimattr_mongodb.yml --delete 20140822185744
```

You can add

```bash
./console mongodb:migrations:version --db-configuration=config/test_antimattr_mongodb.php --configuration=config/test_antimattr_mongodb.yml --add 20140822185744
```

Features - Analyze Migrations
-----------------------------

Identify the collections you want to analyze. Statistics will be captured before and after the migration is run.

```php
class Version20140822185742 extends AbstractMigration
{
    public function up(Database $db)
    {
        $testA = $db->selectCollection('test_a');
        $this->analyze($testA);

        // Do the migration
    }
```

Features - Execute JS Scripts
-----------------------------

First identify the directory for scripts in your Migration configuration

```yaml
---
name: AntiMattr Sandbox Migrations
migrations_namespace: AntiMattrMigrationsTest
database: test_antimattr_migrations
collection_name: antimattr_migration_versions_test
migrations_directory: /path/to/migrations/classes/AntiMattrMigrations
migrations_script_directory: /path/to/migrations/script_directory # optional
```

Then execute the scripts via AbstractMigration::executeScripts

```php
class Version20140822185743 extends AbstractMigration
{
    public function up(Database $db)
    {
        $result = $this->executeScript($db, 'test_script.js');
    }
```

Pull Requests
=============

Pull Requests - PSR Standards
-----------------------------

Please use the pre-commit hook to run the fix all code to PSR standards

Install once with

```bash
./bin/install.sh 
Copying /antimattr-mongodb-migrations/bin/pre-commit.sh -> /antimattr-mongodb-migrations/bin/../.git/hooks/pre-commit
```

Pull Requests - Testing
-----------------------

Please make sure tests pass

```bash
$ vendor/bin/phpunit tests
```

Pull Requests - Code Sniffer and Fixer
--------------------------------------

Don't have the pre-commit hook running, please make sure to run the fixer/sniffer manually

```bash
$ vendor/bin/php-cs-fixer fix src/
$ vendor/bin/php-cs-fixer fix tests/
```
