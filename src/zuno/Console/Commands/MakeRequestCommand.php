<?php

namespace Zuno\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeRequestCommand extends Command
{
    protected static $defaultName = 'make:request';

    protected function configure()
    {
        $this
            ->setName('make:request')
            ->setDescription('Creates a new form request class.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the request class.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        // Split the name by slashes to handle subfolders and class name
        $parts = explode('/', $name);

        // The class name is the last part (after the last slash)
        $className = array_pop($parts);

        // Ensure class name ends with "Request"
        if (!str_ends_with($className, 'Request')) {
            $className .= 'Request';
        }

        // The namespace is the remaining parts joined by backslashes
        $namespace = 'App\\Http\\Validations' . (count($parts) > 0 ? '\\' . implode('\\', $parts) : '');

        // Construct the file path by replacing slashes with directory separators
        $filePath = base_path() . '/app/Http/Validations/' . str_replace('/', DIRECTORY_SEPARATOR, $name) . '.php';

        // Check if the request already exists
        if (file_exists($filePath)) {
            $output->writeln('<error>Request already exists!</error>');
            return Command::FAILURE;
        }

        // Create necessary directories if they don't exist
        $directoryPath = dirname($filePath);
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        // Generate the content for the new request
        $content = $this->generateRequestContent($namespace, $className);

        // Write the new request file to disk
        file_put_contents($filePath, $content);

        // Inform the user that the request has been created
        $output->writeln('<info>Request created successfully</info>');

        return Command::SUCCESS;
    }

    protected function generateRequestContent(string $namespace, string $className): string
    {
        return <<<EOT
<?php

namespace {$namespace};

use Zuno\Http\Validation\FormRequest;

class {$className} extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [];
    }
}
EOT;
    }
}
