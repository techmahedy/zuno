<?php

namespace Zuno\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeControllerCommand extends Command
{
    protected static $defaultName = 'make:controller';

    protected function configure()
    {
        $this
            ->setName('make:controller')
            ->setDescription('Creates a new controller class.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the controller class.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        // Split the name by slashes to handle subfolders and class name
        $parts = explode('/', $name);

        // The class name is the last part (after the last slash)
        $className = array_pop($parts);

        // Ensure class name ends with "Controller"
        if (!str_ends_with($className, 'Controller')) {
            $className .= 'Controller';
        }

        // The namespace is the remaining parts joined by backslashes
        $namespace = 'App\\Http\\Controllers' . (count($parts) > 0 ? '\\' . implode('\\', $parts) : '');

        // Construct the file path by replacing slashes with directory separators
        $filePath = getcwd() . '/app/Http/Controllers/' . str_replace('/', DIRECTORY_SEPARATOR, $name) . '.php';

        // Check if the controller already exists
        if (file_exists($filePath)) {
            $output->writeln('<error>Controller already exists!</error>');
            return Command::FAILURE;
        }

        // Create necessary directories if they don't exist
        $directoryPath = dirname($filePath);
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        // Generate the content for the new controller
        $content = $this->generateControllerContent($namespace, $className);

        // Write the new controller file to disk
        file_put_contents($filePath, $content);

        // Inform the user that the controller has been created
        $output->writeln('<info>Controller created successfully!</info>');

        return Command::SUCCESS;
    }

    protected function generateControllerContent(string $namespace, string $className): string
    {
        return <<<EOT
<?php

namespace {$namespace};

use App\Http\Controllers\Controller;

class {$className} extends Controller
{
    //
}
EOT;
    }
}
