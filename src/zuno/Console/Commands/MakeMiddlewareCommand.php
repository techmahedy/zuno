<?php

namespace Zuno\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeMiddlewareCommand extends Command
{
    protected static $defaultName = 'make:middleware';

    protected function configure()
    {
        $this
            ->setName('make:middleware')
            ->setDescription('Creates a new middleware class.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the middleware class.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        // Split the name by slashes to handle namespaces and class name
        $parts = explode('/', $name);

        // Class name will be the last part (after the last slash)
        $className = array_pop($parts);

        // The namespace is the remaining parts joined by backslashes
        $namespace = 'App\\Http\\Middleware' . (count($parts) > 0 ? '\\' . implode('\\', $parts) : '');

        // Construct the file path by replacing slashes with directory separators
        $filePath = getcwd() . '/app/Http/Middleware/' . str_replace('/', DIRECTORY_SEPARATOR, $name) . '.php';

        // Check if the middleware already exists
        if (file_exists($filePath)) {
            $output->writeln('<error>Middleware already exists!</error>');
            return Command::FAILURE;
        }

        // Create necessary directories if they don't exist
        $directoryPath = dirname($filePath);
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        // Generate the content for the new middleware
        $content = $this->generateMiddlewareContent($namespace, $className);

        // Write the new middleware file to disk
        file_put_contents($filePath, $content);

        // Inform the user that the middleware has been created
        $output->writeln('<info>Middleware created successfully!</info>');

        return Command::SUCCESS;
    }

    protected function generateMiddlewareContent(string $namespace, string $className): string
    {
        return <<<EOT
<?php

namespace {$namespace};

use Closure;
use Zuno\Http\Request;

class {$className}
{
    /**
     * Handle an incoming request.
     *
     * @param Request \$request
     * @param \Closure(\Zuno\Http\Request) \$next
     * @return mixed
     */
    public function handle(Request \$request, Closure \$next)
    {
        return \$next(\$request);
    }
}
EOT;
    }
}
