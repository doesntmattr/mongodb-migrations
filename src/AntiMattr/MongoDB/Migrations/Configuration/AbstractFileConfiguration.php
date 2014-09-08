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

use AntiMattr\MongoDB\Migrations\Exception\ConfigurationFileAlreadyLoadedException;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
abstract class AbstractFileConfiguration extends Configuration
{
    /**
     * The configuration file used to load configuration information
     *
     * @var string
     */
    private $file;

    /**
     * Whether or not the configuration file has been loaded yet or not
     *
     * @var boolean
     */
    private $loaded = false;

    /**
     * Load the information from the passed configuration file
     *
     * @param string $file The path to the configuration file
     *
     * @return void
     *
     * @throws AntiMattr\MongoDB\Migrations\Exception\ConfigurationFileAlreadyLoadedException
     */
    public function load($file)
    {
        if ($this->loaded) {
            throw new ConfigurationFileAlreadyLoadedException('Migrations configuration file already loaded');
        }
        if (file_exists($path = getcwd().'/'.$file)) {
            $file = $path;
        }
        $this->file = $file;
        $this->doLoad($file);
        $this->loaded = true;
    }

    protected function getDirectoryRelativeToFile($file, $input)
    {
        $path = realpath(dirname($file).'/'.$input);
        if ($path !== false) {
            $directory = $path;
        } else {
            $directory = $input;
        }

        return $directory;
    }

    public function getFile()
    {
        return $this->file;
    }

    /**
     * Abstract method that each file configuration driver must implement to
     * load the given configuration file whether it be xml, yaml, etc. or something
     * else.
     *
     * @param string $file The path to a configuration file.
     */
    abstract protected function doLoad($file);
}
