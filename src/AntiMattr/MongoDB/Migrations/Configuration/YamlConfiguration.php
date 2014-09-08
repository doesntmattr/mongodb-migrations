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

use Symfony\Component\Yaml\Yaml;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
class YamlConfiguration extends AbstractFileConfiguration
{
    /**
     * @inheritdoc
     */
    protected function doLoad($file)
    {
        $array = Yaml::parse($file);

        if (isset($array['name'])) {
            $this->setName($array['name']);
        }
        if (isset($array['database'])) {
            $this->setMigrationsDatabaseName($array['database']);
        }
        if (isset($array['collection_name'])) {
            $this->setMigrationsCollectionName($array['collection_name']);
        }
        if (isset($array['migrations_namespace'])) {
            $this->setMigrationsNamespace($array['migrations_namespace']);
        }
        if (isset($array['migrations_directory'])) {
            $migrationsDirectory = $this->getDirectoryRelativeToFile($file, $array['migrations_directory']);
            $this->setMigrationsDirectory($migrationsDirectory);
            $this->registerMigrationsFromDirectory($migrationsDirectory);
        }
        if (isset($array['migrations_script_directory'])) {
            $migrationsDirectory = $this->getDirectoryRelativeToFile($file, $array['migrations_script_directory']);
            $this->setMigrationsScriptDirectory($migrationsDirectory);
        }
        if (isset($array['migrations']) && is_array($array['migrations'])) {
            foreach ($array['migrations'] as $migration) {
                $this->registerMigration($migration['version'], $migration['class']);
            }
        }
    }
}
