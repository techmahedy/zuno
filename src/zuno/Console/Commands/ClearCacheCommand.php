<?php

namespace Zuno\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class ClearCacheCommand extends Command
{
    protected static $defaultName = 'cache:clear';

    protected function configure()
    {
        $this
            ->setName('cache:clear')
            ->setDescription('Clear all cache files from the storage/cache folder.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Forcefully clear the cache without any confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cacheDir = base_path() . '/storage/cache';

        // Confirm with the user unless --force is passed
        if (!$input->getOption('force')) {
            $output->writeln('<question>Do you want to clear all cache files? This action cannot be undone.</question>');
            $confirmation = readline("Type 'yes' to proceed: ");

            if ($confirmation !== 'yes') {
                $output->writeln('<error>Cache clearing aborted.</error>');
                return Command::FAILURE;
            }
        }

        // Check if cache directory exists
        if (!is_dir($cacheDir)) {
            $output->writeln("<error>Cache directory does not exist: $cacheDir</error>");
            return Command::FAILURE;
        }

        // Remove all files from the cache directory
        $files = glob($cacheDir . '/*');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        $output->writeln('<info>Cache cleared successfully!</info>');
        return Command::SUCCESS;
    }
}
