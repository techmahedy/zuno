<?php

namespace Zuno\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Zuno\Config\Config;

class DbSeedCommand extends Command
{
    protected static $defaultName = 'db:seed';

    protected function configure()
    {
        $this
            ->setName('db:seed')
            ->setDescription('Run database seeds.')
            ->addArgument('seed', InputArgument::OPTIONAL, 'The name of the seed to run (optional).');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        Config::initialize();

        $configPath = base_path() . '/config.php';
        $seedName = $input->getArgument('seed');
        $phinxCommand = "php vendor/bin/phinx seed:run -c $configPath";

        // If a specific seed is provided, add it to the command
        if ($seedName) {
            $phinxCommand .= " -s $seedName";
        }

        // Execute the Phinx command
        exec($phinxCommand, $result, $status);

        // Output the result
        foreach ($result as $line) {
            $output->writeln($line);
        }

        if ($status !== 0) {
            $output->writeln('<error>Failed to run seeds.</error>');
            return Command::FAILURE;
        }

        $output->writeln('<info>Seeds executed successfully</info>');
        return Command::SUCCESS;
    }
}
