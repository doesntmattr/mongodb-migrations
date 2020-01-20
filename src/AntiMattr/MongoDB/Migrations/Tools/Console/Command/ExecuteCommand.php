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

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

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
            ->addOption('replay', null, InputOption::VALUE_NONE, 'Replay an \'up\' migration and avoid the duplicate exception.')
            ->setHelp(<<<'EOT'
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
     * @param \Symfony\Component\Console\Input\InputInterface
     * @param \Symfony\Component\Console\Output\OutputInterface
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $version = $input->getArgument('version');
        $direction = $input->getOption('down') ? 'down' : 'up';
        $replay = $input->getOption('replay');

        $configuration = $this->getMigrationConfiguration($input, $output);
        $version = $configuration->getVersion($version);

        if (!$input->isInteractive()) {
            $version->execute($direction, $replay);
        } else {
            $question = new ConfirmationQuestion(
                '<question>WARNING! You are about to execute a database migration that could result in data lost. Are you sure you wish to continue? (y/[n])</question> ',
                false
            );

            $confirmation = $this
                ->getHelper('question')
                ->ask($input, $output, $question);

            if (true === $confirmation) {
                $version->execute($direction, $replay);
            } else {
                $output->writeln('<error>Migration cancelled!</error>');
            }
        }

        return 0;
    }

    public function getName()
    {
        return self::NAME;
    }
}
