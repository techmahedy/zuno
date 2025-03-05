<?php

namespace Zuno\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;

class StartServerCommand extends Command
{
    protected static $defaultName = 'start';

    protected function configure()
    {
        $this
            ->setName('start')
            ->setDescription('Start the Zuno development server.')
            ->addOption('port', null, InputOption::VALUE_REQUIRED, 'The port to run the server on', 8000);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $port = $input->getOption('port');

        // Check if the port is already in use
        if ($this->isPortInUse($port)) {
            $output->writeln("<error>Port $port is already in use!</error>");
            return Command::FAILURE;
        }

        $output->writeln("<info>Starting server on http://localhost:$port</info>");

        // Start PHP built-in server
        $process = new Process(["php", "-S", "localhost:$port", "-t", "public"]);
        $process->setTimeout(0); // Keep the process running indefinitely
        $process->start();

        // Stream server output
        foreach ($process as $type => $data) {
            $output->write($data);
        }

        return Command::SUCCESS;
    }

    private function isPortInUse($port): bool
    {
        $connection = @fsockopen('localhost', $port);
        if ($connection) {
            fclose($connection);
            return true;
        }
        return false;
    }
}
