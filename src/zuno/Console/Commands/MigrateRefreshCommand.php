<?php

namespace Zuno\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class MigrateRefreshCommand extends Command
{
    protected static $defaultName = 'migrate:fresh';

    protected function configure()
    {
        $this
            ->setName('migrate:fresh')
            ->setDescription('Rolls back all migrations and re-runs them');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Rollback all migrations
        $rollbackProcess = new Process(['php', 'vendor/bin/phinx', 'rollback', '-t', '0', '-c', 'config.php']);
        $rollbackProcess->run();

        if (!$rollbackProcess->isSuccessful()) {
            throw new ProcessFailedException($rollbackProcess);
        }

        // Run migrations
        $migrateProcess = new Process(['php', 'vendor/bin/phinx', 'migrate', '-c', 'config.php']);
        $migrateProcess->run();

        if (!$migrateProcess->isSuccessful()) {
            throw new ProcessFailedException($migrateProcess);
        }

        $output->writeln('<info>All migrations have been refreshed successfully.</info>');
        return Command::SUCCESS;
    }
}
