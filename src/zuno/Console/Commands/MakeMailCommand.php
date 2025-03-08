<?php

namespace Zuno\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class MakeMailCommand extends Command
{
    protected static $defaultName = 'make:mail';

    protected function configure()
    {
        $this
            ->setName('make:mail')
            ->setDescription('Creates a new Mailable class.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the Mailable class.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        // Split the name by slashes to handle subfolders and class name
        $parts = explode('/', $name);

        // The class name is the last part (after the last slash)
        $className = array_pop($parts);

        // Ensure class name ends with "Mail"
        if (!str_ends_with($className, 'Mail')) {
            $className .= 'Mail';
        }

        // The namespace is the remaining parts joined by backslashes
        $namespace = 'App\\Mail' . (count($parts) > 0 ? '\\' . implode('\\', $parts) : '');

        // Construct the file path by replacing slashes with directory separators
        $filePath = base_path() . '/app/Mail/' . str_replace('/', DIRECTORY_SEPARATOR, $name) . '.php';

        // Check if the Mailable already exists
        if (file_exists($filePath)) {
            $output->writeln('<error>Mailable already exists!</error>');
            return Command::FAILURE;
        }

        // Create necessary directories if they don't exist
        $directoryPath = dirname($filePath);
        if (!is_dir($directoryPath)) {
            mkdir($directoryPath, 0755, true);
        }

        // Generate the content for the new Mailable
        $content = $this->generateMailContent($namespace, $className);

        // Write the new Mailable file to disk
        file_put_contents($filePath, $content);

        // Inform the user that the Mailable has been created
        $output->writeln('<info>Mailable created successfully</info>');

        return Command::SUCCESS;
    }

    protected function generateMailContent(string $namespace, string $className): string
    {
        return <<<EOT
<?php

namespace {$namespace};

use Zuno\Support\Mail\Mailable;
use Zuno\Support\Mail\Mailable\Subject;
use Zuno\Support\Mail\Mailable\Content;

class {$className} extends Mailable
{
    /**
     * Create a new message instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Define mail subject
     * @return Zuno\Support\Mail\Mailable\Subject
     */
    public function subject(): Subject
    {
        return new Subject(
            subject: 'New Mail'
        );
    }

    /**
     * Set the message body and data
     * @return Zuno\Support\Mail\Mailable\Content
     */
    public function content(): Content
    {
        return new Content(
            view: 'Optional view.name',
            data: 'Optional data'
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array
     */
    public function attachment(): array
    {
        return [];
    }
}
EOT;
    }
}
