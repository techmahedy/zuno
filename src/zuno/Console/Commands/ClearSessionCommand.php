<?php

namespace Zuno\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class ClearSessionCommand extends Command
{
    protected static $defaultName = 'session:clear';

    protected function configure()
    {
        $this
            ->setName('session:clear')
            ->setDescription('Clear all session files from the storage/sessions folder.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Forcefully clear the session files without confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $sessionDir = base_path('storage/sessions');

        // Confirm with the user unless --force is passed
        if (!$input->getOption('force')) {
            $output->writeln('<question>Do you want to clear all session files? This action cannot be undone.</question>');
            $confirmation = readline("Type 'yes' to proceed: ");

            if ($confirmation !== 'yes') {
                $output->writeln('<error>Session clearing aborted.</error>');
                return Command::FAILURE;
            }
        }

        // Check if session directory exists
        if (!is_dir($sessionDir)) {
            $output->writeln("<error>Session directory does not exist: $sessionDir</error>");
            return Command::FAILURE;
        }

        // Remove all session files
        $files = glob($sessionDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        $output->writeln('<info>All session files cleared successfully!</info>');
        return Command::SUCCESS;
    }
}
