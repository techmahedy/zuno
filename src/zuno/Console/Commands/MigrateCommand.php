<?php

namespace Zuno\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Phinx\Config\Config;
use Phinx\Migration\Manager;

class MigrateCommand extends Command
{
    protected static $defaultName = 'migrate';

    protected function configure()
    {
        $this
            ->setName('migrate')
            ->setDescription('Runs new database migrations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $configPath = base_path() . '/config.php';
        if (!file_exists($configPath)) {
            $output->writeln('<error>Phinx configuration file not found at ' . $configPath . '</error>');
            return Command::FAILURE;
        }

        $configArray = include $configPath;
        $config = new Config($configArray);

        // Get the environment name from the config (e.g. "zuno")
        $env = $config->getDefaultEnvironment();

        // Fetch the environment configuration
        $environmentConfig = $config->getEnvironment($env);

        if ($environmentConfig === null) {
            $output->writeln('<error>Environment configuration not found for: ' . $env . '</error>');
            return Command::FAILURE;
        }

        // Get the adapter (e.g., MySQL or SQLite)
        $adapter = $environmentConfig['adapter'];

        // Initialize the PDO connection based on the adapter
        if ($adapter === 'mysql') {
            $pdo = new \PDO(
                'mysql:host=' . $environmentConfig['host'] . ';dbname=' . $environmentConfig['name'],
                $environmentConfig['user'],
                $environmentConfig['pass']
            );
        } else {
            // Add support for other adapters if necessary (e.g., SQLite, PostgreSQL)
            $output->writeln('<error>Unsupported adapter: ' . $adapter . '</error>');
            return Command::FAILURE;
        }

        // Check if the migrations table exists
        $tableCheck = $pdo->query("SHOW TABLES LIKE 'migrations'");
        if (!$tableCheck->rowCount()) {
            // Table doesn't exist, so create it
            $output->writeln('<info>Creating migrations table...</info>');
            $createTableSql = "
                CREATE TABLE migrations (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    migration_name VARCHAR(255) NOT NULL,
                    version BIGINT NOT NULL,
                    start_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    end_time TIMESTAMP NULL
                );
            ";
            $pdo->exec($createTableSql);
            $output->writeln('<info>Created migrations table successfully.</info>');
        }

        // Get list of already migrated files
        $stmt = $pdo->query("SELECT migration_name FROM migrations");
        $migratedFiles = $stmt->fetchAll(\PDO::FETCH_COLUMN);

        // Run only new migrations
        $manager = new Manager($config, $input, $output);
        $migrations = $manager->getMigrations($env); // Pass environment name here

        $pendingMigrations = array_filter($migrations, function ($migration) use ($migratedFiles) {
            return !in_array($migration->getName(), $migratedFiles); // Check against migration names
        });

        if (empty($pendingMigrations)) {
            $output->writeln('<comment>No new migrations to run.</comment>');
            return Command::SUCCESS;
        }

        $output->writeln('<info>Running new migrations...</info>');

        // Process and apply new migrations one by one
        foreach ($pendingMigrations as $migration) {
            $process = new Process(['php', 'vendor/bin/phinx', 'migrate', '-c', $configPath, '--target=' . $migration->getVersion()]);
            $process->run();

            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            // Output the migration file name after it has been applied successfully
            $output->writeln('<info>' . $migration->getName() . ' migration applied successfully.</info>');
        }

        return Command::SUCCESS;
    }
}
