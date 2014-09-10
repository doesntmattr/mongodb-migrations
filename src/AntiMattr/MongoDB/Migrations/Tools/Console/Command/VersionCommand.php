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

use AntiMattr\MongoDB\Migrations\Exception\UnknownVersionException;
use AntiMattr\MongoDB\Migrations\Migration;
use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
class VersionCommand extends AbstractCommand
{
    const NAME = 'mongodb:migrations:version';

    protected function configure()
    {
        $this
            ->setName($this->getName())
            ->setDescription('Manually add and delete migration versions from the version table.')
            ->addArgument('version', InputArgument::REQUIRED, 'The version to add or delete.', null)
            ->addOption('add', null, InputOption::VALUE_NONE, 'Add the specified version.')
            ->addOption('delete', null, InputOption::VALUE_NONE, 'Delete the specified version.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command allows you to manually add and delete migration versions from the version table:

    <info>%command.full_name% YYYYMMDDHHMMSS --add</info>

If you want to delete a version you can use the <comment>--delete</comment> option:

    <info>%command.full_name% YYYYMMDDHHMMSS --delete</info>
EOT
        );

        parent::configure();
    }

    /**
     * @param Symfony\Component\Console\Input\InputInterface
     * @param Symfony\Component\Console\Output\OutputInterface
     *
     * @throws AntiMattr\MongoDB\Migrations\Exception\UnknownVersionException Throws exception if migration version does not exist.
     * @throws InvalidArgumentException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $configuration = $this->getMigrationConfiguration($input, $output);
        $migration = $this->createMigration($configuration);

        if ($input->getOption('add') === false && $input->getOption('delete') === false) {
            throw new \InvalidArgumentException('You must specify whether you want to --add or --delete the specified version.');
        }

        $version = $input->getArgument('version');
        $markMigrated = $input->getOption('add') ? true : false;

        if (!$configuration->hasVersion($version)) {
            throw new UnknownVersionException($version);
        }

        $version = $configuration->getVersion($version);
        if ($markMigrated && $configuration->hasVersionMigrated($version)) {
            throw new \InvalidArgumentException(sprintf('The version "%s" already exists in the version collection.', $version));
        }

        if (!$markMigrated && !$configuration->hasVersionMigrated($version)) {
            throw new \InvalidArgumentException(sprintf('The version "%s" does not exists in the version collection.', $version));
        }

        if ($markMigrated) {
            $version->markMigrated();
        } else {
            $version->markNotMigrated();
        }
    }

    protected function createMigration(Configuration $configuration)
    {
        return new Migration($configuration);
    }

    public function getName()
    {
        return self::NAME;
    }
}
