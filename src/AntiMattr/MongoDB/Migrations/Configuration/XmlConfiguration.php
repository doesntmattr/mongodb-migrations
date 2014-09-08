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

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
class XmlConfiguration extends AbstractFileConfiguration
{
    /**
     * @inheritdoc
     */
    protected function doLoad($file)
    {
        $xml = simplexml_load_file($file);
        if (isset($xml->name)) {
            $this->setName((string) $xml->name);
        }
        if (isset($xml->database['name'])) {
            $this->setMigrationsDatabaseName((string) $xml->database['name']);
        }
        if (isset($xml->collection['name'])) {
            $this->setMigrationsCollectionName((string) $xml->collection['name']);
        }
        if (isset($xml->{'migrations-namespace'})) {
            $this->setMigrationsNamespace((string) $xml->{'migrations-namespace'});
        }
        if (isset($xml->{'migrations-directory'})) {
            $migrationsDirectory = $this->getDirectoryRelativeToFile($file, (string) $xml->{'migrations-directory'});
            $this->setMigrationsDirectory($migrationsDirectory);
            $this->registerMigrationsFromDirectory($migrationsDirectory);
        }
        if (isset($xml->{'migrations-script-directory'})) {
            $migrationsDirectory = $this->getDirectoryRelativeToFile($file, (string) $xml->{'migrations-script-directory'});
            $this->setMigrationsScriptDirectory($migrationsDirectory);
        }
        if (isset($xml->migrations->migration)) {
            foreach ($xml->migrations->migration as $migration) {
                $this->registerMigration((string) $migration['version'], (string) $migration['class']);
            }
        }
    }
}
