<?php

namespace Zuno\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class ClearConfigCommand extends Command
{
    protected static $defaultName = 'config:clear';

    protected function configure()
    {
        $this
            ->setName('config:clear')
            ->setDescription('Clear all config cache data.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Forcefully clear the cache without any confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cacheDir = base_path() . '/storage/framework/cache';

        // Confirm with the user unless --force is passed
        if (!$input->getOption('force')) {
            $output->writeln('<question>Do you want to clear all cache files? This action cannot be undone.</question>');
            $confirmation = readline("Type 'yes' to proceed: ");

            if ($confirmation !== 'yes') {
                $output->writeln('<error>Config clearing aborted.</error>');
                return Command::FAILURE;
            }
        }

        // Check if cache directory exists
        if (!is_dir($cacheDir)) {
            $output->writeln("<error>Cache directory does not exist: $cacheDir</error>");
            return Command::FAILURE;
        }

        // Recursively delete all files and subdirectories
        $this->deleteDirectoryContents($cacheDir);

        $output->writeln('<info>Config cache cleared successfully</info>');
        return Command::SUCCESS;
    }

    /**
     * Recursively delete all files and subdirectories in a directory.
     *
     * @param string $directory
     */
    private function deleteDirectoryContents($directory)
    {
        $files = array_diff(scandir($directory), ['.', '..']);

        foreach ($files as $file) {
            $filePath = $directory . DIRECTORY_SEPARATOR . $file;
            if (is_dir($filePath)) {
                $this->deleteDirectoryContents($filePath);
                rmdir($filePath);
            } else {
                unlink($filePath);
            }
        }
    }
}
