<?php

namespace Zuno\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeProviderCommand extends Command
{
    protected static $defaultName = 'make:provider';

    protected function configure()
    {
        $this
            ->setName('make:provider')
            ->setDescription('Creates a new service provider class.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the provider class.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        // Ensure class name ends with "Provider"
        if (!str_ends_with($name, 'Provider')) {
            $name .= 'Provider';
        }

        $namespace = 'App\\Providers';
        $filePath = base_path() . '/app/Providers/' . $name . '.php';

        // Check if the provider already exists
        if (file_exists($filePath)) {
            $output->writeln('<error>Provider already exists!</error>');
            return Command::FAILURE;
        }

        // Create the Providers directory if it doesn't exist
        $directoryPath = dirname($filePath);
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        // Generate the content for the new provider
        $content = $this->generateProviderContent($namespace, $name);

        // Write the new provider file to disk
        file_put_contents($filePath, $content);

        // Inform the user that the provider has been created
        $output->writeln('<info>Provider created successfully</info>');

        return Command::SUCCESS;
    }

    protected function generateProviderContent(string $namespace, string $className): string
    {
        return <<<EOT
<?php

namespace {$namespace};

use Zuno\Providers\ServiceProvider;

class {$className} extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        //
    }
}
EOT;
    }
}
