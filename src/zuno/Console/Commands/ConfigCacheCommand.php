<?php

namespace Zuno\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class ConfigCacheCommand extends Command
{
    protected static $defaultName = 'config:cache';

    /**
     * Stores loaded configuration data.
     *
     * @var array<string, mixed>
     */
    protected array $config = [];

    /**
     * Cache file path.
     *
     * @var string
     */
    protected string $cacheFile;

    protected function configure()
    {
        $this
            ->setName('config:cache')
            ->setDescription('Cache the configuration files into a single file for faster access.')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Forcefully cache the config without any confirmation');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->initializeFilePath();

        if (!$input->getOption('force')) {
            $output->writeln('<question>Do you want to cache all the config files? This action cannot be undone.</question>');
            $confirmation = readline("Type 'yes' to proceed: ");

            if ($confirmation !== 'yes') {
                $output->writeln('<error>Config caching aborted.</error>');
                return Command::FAILURE;
            }
        }

        $this->loadAll();
        $this->cacheConfig();

        $output->writeln('<info>Config cached successfully!</info>');
        return Command::SUCCESS;
    }

    /**
     * Initialize cache file path.
     */
    protected function initializeFilePath(): void
    {
        $this->cacheFile = base_path() . '/storage/framework/cache/config.php';
    }

    /**
     * Load all configuration files.
     */
    protected function loadAll(): void
    {
        foreach (glob(base_path() . '/config/*.php') as $file) {
            $fileName = basename($file, '.php');
            $this->config[$fileName] = include $file;
        }
    }

    /**
     * Cache the configuration to a file.
     */
    protected function cacheConfig(): void
    {
        $cacheDir = dirname($this->cacheFile);
        if (!is_dir($cacheDir)) {
            if (!mkdir($cacheDir, 0777, true) && !is_dir($cacheDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $cacheDir));
            }
        }

        $content = '<?php return ' . var_export($this->config, true) . ';';
        file_put_contents($this->cacheFile, $content);
    }
}
