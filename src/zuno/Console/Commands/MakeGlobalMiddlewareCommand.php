<?php

namespace Zuno\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeGlobalMiddlewareCommand extends Command
{
    protected static $defaultName = 'make:gmiddleware';

    protected function configure()
    {
        $this
            ->setName('make:gmiddleware')
            ->setDescription('Creates a new global middleware class.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the middleware class.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        // Split the name by slashes to handle subfolders and class name
        $parts = explode('/', $name);

        // The class name is the last part (after the last slash)
        $className = array_pop($parts);

        // Default namespace with support for subdirectories
        $namespace = 'App\\Http\\Middleware' . (count($parts) > 0 ? '\\' . implode('\\', $parts) : '');

        // Construct the file path correctly inside app/Http/Middleware/
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
        $output->writeln('<info>Global Middleware created successfully!</info>');

        return Command::SUCCESS;
    }

    protected function generateMiddlewareContent(string $namespace, string $className): string
    {
        return <<<EOT
<?php

namespace {$namespace};

use Closure;
use Zuno\Http\Request;
use Zuno\Middleware\Contracts\Middleware;

class {$className} implements Middleware
{
    /**
     * Handles an incoming request.
     * This middleware processes the request before passing it to the next handler.
     *
     * @param Request \$request
     * @param Closure \$next
     * @return mixed
     */
    public function __invoke(Request \$request, Closure \$next): mixed
    {
        return \$next(\$request);
    }
}
EOT;
    }
}
