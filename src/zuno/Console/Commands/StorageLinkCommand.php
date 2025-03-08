<?php

namespace Zuno\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Filesystem;

class StorageLinkCommand extends Command
{
    protected static $defaultName = 'storage:link';

    protected function configure()
    {
        $this
            ->setName('storage:link')
            ->setDescription('Create a symbolic link from public/storage to storage/app/public.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $filesystem = new Filesystem();

        $publicStorage = base_path('public/storage');
        $target = base_path('storage/app/public');

        // Check if symlink already exists
        if (is_link($publicStorage)) {
            $output->writeln('<comment>Storage link already exists.</comment>');
            return Command::SUCCESS;
        }

        // Remove directory if it exists (to prevent conflicts)
        if (file_exists($publicStorage)) {
            $filesystem->remove($publicStorage);
        }

        try {
            // Create the symbolic link
            $filesystem->symlink($target, $publicStorage);
            $output->writeln('<info>Symbolic link created successfully</info>');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $output->writeln('<error>Failed to create symbolic link: ' . $e->getMessage() . '</error>');
            return Command::FAILURE;
        }
    }
}
