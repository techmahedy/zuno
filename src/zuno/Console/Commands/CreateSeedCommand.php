<?php

namespace Zuno\Console\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateSeedCommand extends Command
{
    protected static $defaultName = 'create:seed';

    protected function configure()
    {
        $this
            ->setName('create:seed')
            ->setDescription('Creates a new Phinx seed class.')
            ->addArgument('name', InputArgument::REQUIRED, 'The name of the seed class.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $name = $input->getArgument('name');

        // Ensure the seed name ends with "Seeder"
        if (!str_ends_with($name, 'Seeder')) {
            $name .= 'Seeder';
        }

        // Define the seed file path
        $filePath = base_path() . '/database/seeds/' . $name . '.php';

        // Check if the seed file already exists
        if (file_exists($filePath)) {
            $output->writeln('<error>Seed file already exists!</error>');
            return Command::FAILURE;
        }

        // Generate the content for the new seed
        $content = $this->generateSeedContent($name);

        // Write the new seed file to disk
        file_put_contents($filePath, $content);

        // Inform the user that the seed has been created
        $output->writeln('<info>Seed file created successfully</info>');

        return Command::SUCCESS;
    }

    protected function generateSeedContent(string $className): string
    {
        return <<<EOT
<?php

declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class {$className} extends AbstractSeed
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * https://book.cakephp.org/phinx/0/en/seeding.html
     */
    public function run(): void
    {

    }
}
EOT;
    }
}
