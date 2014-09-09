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

use AntiMattr\MongoDB\Migrations\Migration;
use AntiMattr\MongoDB\Migrations\Configuration\Configuration;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @author Matthew Fitzgerald <matthewfitz@gmail.com>
 */
class ExecuteCommand extends AbstractCommand
{
    const NAME = 'mongodb:migrations:execute';

    protected function configure()
    {
        $this
            ->setName($this->getName())
            ->setDescription('Execute a single migration version up or down manually.')
            ->addArgument('version', InputArgument::REQUIRED, 'The version to execute.', null)
            ->addOption('up', null, InputOption::VALUE_NONE, 'Execute the migration up.')
            ->addOption('down', null, InputOption::VALUE_NONE, 'Execute the migration down.')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command executes a single migration version up or down manually:

    <info>%command.full_name% YYYYMMDDHHMMSS</info>

If no <comment>--up</comment> or <comment>--down</comment> option is specified it defaults to up:

    <info>%command.full_name% YYYYMMDDHHMMSS --down</info>

Or you can also execute the migration without a warning message which you need to interact with:

    <info>%command.full_name% --no-interaction</info>
EOT
        );

        parent::configure();
    }

    /**
     * @param Symfony\Component\Console\Input\InputInterface
     * @param Symfony\Component\Console\Output\OutputInterface
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $version = $input->getArgument('version');
        $direction = $input->getOption('down') ? 'down' : 'up';

        $configuration = $this->getMigrationConfiguration($input, $output);
        $version = $configuration->getVersion($version);

        if (!$input->isInteractive()) {
            $version->execute($direction);
        } else {
            $confirmation = $this->getHelper('dialog')->askConfirmation($output, '<question>WARNING! You are about to execute a database migration that could result in data lost. Are you sure you wish to continue? (y/n)</question>', false);
            if ($confirmation === true) {
                $version->execute($direction);
            } else {
                $output->writeln('<error>Migration cancelled!</error>');
            }
        }
    }

    public function getName()
    {
        return self::NAME;
    }
}
