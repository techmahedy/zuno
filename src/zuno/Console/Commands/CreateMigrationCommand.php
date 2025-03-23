<?php

namespace Zuno\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateMigrationCommand extends Command
{
    protected static $defaultName = 'make:migration';

    protected function configure()
    {
        $this
            ->setName('make:migration')
            ->setDescription('Creates a new migration class.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the migration class.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        // Ensure the migration name is in PascalCase
        $className = $this->pascalCase($name);

        // Define the migration file path
        $timestamp = date('YmdHis'); // Timestamp for the filename
        $filePath = base_path() . '/database/migrations/' . $timestamp . '_' . $this->snakeCase($name) . '.php';

        // Check if the migration file already exists
        if (file_exists($filePath)) {
            $output->writeln('<error>Migration file already exists!</error>');
            return Command::FAILURE;
        }

        // Generate the content for the new migration
        $content = $this->generateMigrationContent($className);

        // Write the new migration file to disk
        file_put_contents($filePath, $content);

        // Inform the user that the migration has been created
        $output->writeln('<info>Migration file created successfully</info>');

        return Command::SUCCESS;
    }

    protected function generateMigrationContent(string $className): string
    {
        return <<<EOT
<?php

declare(strict_types=1);

use Zuno\Migration\Migration;

final class {$className} extends Migration
{
    // Define the change method, which is used to define the changes to the database schema
    public function change(): void
    {
        \$this->table('{$this->snakeCase($className)}')
            ->addColumn('created_at', 'timestamp', ['null' => true])
            ->addColumn('updated_at', 'timestamp', ['null' => true])
            ->create();
    }
}
EOT;
    }

    /**
     * Convert a string to PascalCase.
     *
     * @param string $input The input string.
     * @return string The PascalCase string.
     */
    protected function pascalCase(string $input): string
    {
        return str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $input)));
    }

    /**
     * Convert a string to snake_case.
     *
     * @param string $input The input string.
     * @return string The snake_case string.
     */
    protected function snakeCase(string $input): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $input));
    }
}
