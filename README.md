[![MIT license](http://img.shields.io/badge/license-MIT-brightgreen.svg)](http://opensource.org/licenses/MIT)
[![Build Status](https://travis-ci.org/doesntmattr/mongodb-migrations.png?branch=master)](https://travis-ci.org/doesntmattr/mongodb-migrations)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/doesntmattr/mongodb-migrations/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/doesntmattr/mongodb-migrations/?branch=master)
[![Latest Stable Version](https://poser.pugx.org/doesntmattr/mongodb-migrations/v/stable)](https://packagist.org/packages/doesntmattr/mongodb-migrations)
[![Total Downloads](https://poser.pugx.org/doesntmattr/mongodb-migrations/downloads)](https://packagist.org/packages/doesntmattr/mongodb-migrations)

# MongoDB Migrations

The MongoDB Migration library provides managed migration support for MongoDB. It was moved to the doesntmattr organisation from [antimattr/mongodb-migrations](https://github.com/antimattr/mongodb-migrations) to continue maintenance (See [issue 16](https://github.com/antimattr/mongodb-migrations/issues/16)).

The original authors are @rcatlin and @matthewfitz

It follows the structure and features provided by [Doctrine Migrations](https://github.com/doctrine/migrations).

## PHP Version Support

If you require php 5.6 support use version `^1.0`. Version `^2.0` requires at least php 7.1. The `1.x` releases will only receive bug fixes.

## Symfony Bundle

There is a Symfony Bundle you can install to more easily integrate with Symfony. Use the installation instructions there:

https://github.com/doesntmattr/mongodb-migrations-bundle

## Installation

To install with composer:

```bash
# For php 5.6
composer require "doesntmattr/mongodb-migrations=^1.0"

# For php 7.1
composer require "doesntmattr/mongodb-migrations=^2.0"
```

## Features

### Configuration

Similar to [Doctrine Migrations](https://github.com/doctrine/migrations), configuration is separated into 2 files:

 * Connection configuration (php)
 * Migration configuration (xml or yaml)

Example connection configuration "test\_antimattr\_mongodb.php":

```php
/**
 * @link http://php.net/manual/en/mongoclient.construct.php
 */
return [
    'host' => 'localhost', // default is localhost
    'port' => '27017', // default is 27017
    'dbname' => null, // optional, if authentication DB is required
    'user' => null, // optional, if authentication is required
    'password' => null, // optional, if authentication is required
    'options' => [
        'connect' => true // recommended
    ]
];
```

XML or YAML migration configuration files are supported.

Example XML "test\_antimattr\_mongodb.xml":

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

Example YAML "test\_antimattr\_mongodb.yml":

```yaml
---
name: AntiMattr Sandbox Migrations
migrations_namespace: AntiMattrMigrationsTest
database: test_antimattr_migrations
collection_name: antimattr_migration_versions_test
migrations_directory: /path/to/migrations/classes/AntiMattrMigrations
migrations_script_directory: /path/to/migrations/script_directory # optional
```

### Console Command Support

There is an example Console Application in the `/demo` directory.

This is how you can register commands in your application:

```php
require '../../vendor/autoload.php';

error_reporting(E_ALL & ~E_NOTICE);

use AntiMattr\MongoDB\Migrations\Tools\Console\Command as AntiMattr;
use Symfony\Component\Console\Application;

$application = new Application();
$application->addCommands([
    new AntiMattr\ExecuteCommand(),
    new AntiMattr\GenerateCommand(),
    new AntiMattr\MigrateCommand(),
    new AntiMattr\StatusCommand(),
    new AntiMattr\VersionCommand()
]);
$application->run();
```

Notice the console is executable:

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

### Generate a New Migration

```bash
> ./console mongodb:migrations:generate --db-configuration=config/test_antimattr_mongodb.php --configuration=config/test_antimattr_mongodb.yml
Generated new migration class to "Example/Migrations/TestAntiMattr/MongoDB/Version20140822185742.php"
```

### Migrations Status

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

### Migrate all

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

### Execute a Single Migration

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

If you need to run a migration again, you can use the `--replay` argument.


### Version Up or Down

Is your migration history out of sync for some reason? You can manually add or remove a record from the history without running the underlying migration.

You can delete:

```bash
./console mongodb:migrations:version --db-configuration=config/test_antimattr_mongodb.php --configuration=config/test_antimattr_mongodb.yml --delete 20140822185744
```

You can add:

```bash
./console mongodb:migrations:version --db-configuration=config/test_antimattr_mongodb.php --configuration=config/test_antimattr_mongodb.yml --add 20140822185744
```

### Analyze Migrations

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

### Execute JS Scripts

First identify the directory for scripts in your Migration Configuration:

```yaml
---
name: AntiMattr Sandbox Migrations
migrations_namespace: AntiMattrMigrationsTest
database: test_antimattr_migrations
collection_name: antimattr_migration_versions_test
migrations_directory: /path/to/migrations/classes/AntiMattrMigrations
migrations_script_directory: /path/to/migrations/script_directory # optional
```

Then execute the scripts via `AbstractMigration::executeScript()`:

```php
class Version20140822185743 extends AbstractMigration
{
    public function up(Database $db)
    {
        $result = $this->executeScript($db, 'test_script.js');
    }
```

## Contributing

### PSR Standards

There is a git pre-commit hook that will fix all your contributed code to PSR standards.

You can install it with:

```bash
./bin/install.sh 
Copying /antimattr-mongodb-migrations/bin/pre-commit.sh -> /antimattr-mongodb-migrations/bin/../.git/hooks/pre-commit
```

### Testing

Tests should pass:

```bash
$ ./vendor/bin/phpunit
```

### Code Sniffer and Fixer

If you didn't install the git pre-commit hook then ensure you run the fixer/sniffer manually:

```bash
$ vendor/bin/php-cs-fixer fix src/
$ vendor/bin/php-cs-fixer fix tests/
```
