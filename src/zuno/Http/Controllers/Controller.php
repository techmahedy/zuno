<?php

namespace Zuno\Http\Controllers;

use RuntimeException;
use Countable;
use Zuno\Support\View\View;
use Zuno\Support\Cache\Cache;
use Zuno\Support\Blade\BladeCompiler;
use Zuno\Support\Blade\BladeCondition;
use Zuno\Support\Blade\Directives;

class Controller extends View
{
    use Cache, BladeCompiler, Directives, BladeCondition;

    protected $loopStacks = [];

    protected $emptyCounter = 0;

    protected $firstCaseSwitch = true;

    /**
     * Constructor to initialize the template engine with default settings
     */
    public function __construct()
    {
        // Set the file extension for template files
        $this->setFileExtension('.blade.php');

        // Set the directory where view files are stored
        $this->setViewFolder('resources/views' . DIRECTORY_SEPARATOR);

        // Set the directory where cached files are stored
        $this->setCacheFolder('storage/cache' . DIRECTORY_SEPARATOR);

        // Create the cache folder if it doesn't exist
        $this->createCacheFolder();

        // Set the format for echoing variables in templates
        $this->setEchoFormat('$this->e(%s)');

        // Initialize arrays for blocks, block stacks, and loop stacks
        $this->blocks = [];
        $this->blockStacks = [];
        $this->loopStacks = [];
    }

    /**
     * Set file extension for the view files
     * Default to: '.blade.php'.
     *
     * @param string $extension
     */
    public function setFileExtension($extension): void
    {
        $this->fileExtension = $extension;
    }

    /**
     * Set view folder location
     * Default to: './views'.
     *
     * @param string $value
     */
    public function setViewFolder($path): void
    {
        $this->viewFolder = str_replace('/', DIRECTORY_SEPARATOR, $path);
    }

    /**
     * Set echo format
     * Default to: '$this->e($data)'.
     *
     * @param string $format
     */
    public function setEchoFormat($format): void
    {
        $this->echoFormat = $format;
    }

    /**
     * Prepare the view file (locate and extract).
     *
     * @param string $view
     */
    protected function prepare($view): string
    {
        $view = str_replace(['.', '/'], DIRECTORY_SEPARATOR, ltrim($view, '/'));
        $actual = base_path() . '/' . $this->viewFolder . DIRECTORY_SEPARATOR . $view . $this->fileExtension;

        $view = str_replace(['/', '\\', DIRECTORY_SEPARATOR], '.', $view);
        $cache = base_path() . '/' . $this->cacheFolder . DIRECTORY_SEPARATOR . $view . '__' . sprintf('%u', crc32($view)) . '.php';

        if (!is_file($cache) || filemtime($actual) > filemtime($cache)) {
            if (!is_file($actual)) {
                throw new RuntimeException('View file not found: ' . $actual);
            }

            $content = file_get_contents($actual);
            // Add @set() directive using extend() method, we need 2 parameters here
            $this->extend(function ($value) {
                return preg_replace("/@set\(['\"](.*?)['\"]\,(.*)\)/", '<?php $$1 =$2; ?>', $value);
            });

            $compilers = ['Statements', 'Comments', 'Echos', 'Extensions'];

            foreach ($compilers as $compiler) {
                $content = $this->{'compile' . $compiler}($content);
            }

            // Replace @php and @endphp blocks
            $content = $this->replacePhpBlocks($content);

            file_put_contents($cache, $content);
        }

        return $cache;
    }

    /**
     * Add new loop to the stack.
     *
     * @param mixed $data
     */
    public function addLoop($data): void
    {
        $length = (is_array($data) || $data instanceof Countable) ? count($data) : null;
        $parent = empty($this->loopStacks) ? null : end($this->loopStacks);
        $this->loopStacks[] = [
            'iteration' => 0,
            'index' => 0,
            'remaining' => isset($length) ? $length : null,
            'count' => $length,
            'first' => true,
            'last' => isset($length) ? ($length === 1) : null,
            'depth' => count($this->loopStacks) + 1,
            'parent' => $parent ? (object) $parent : null,
        ];
    }

    /**
     * Increment the top loop's indices.
     *
     * @return void
     */
    public function incrementLoopIndices(): void
    {
        $loop = &$this->loopStacks[count($this->loopStacks) - 1];
        $loop['iteration']++;
        $loop['index'] = $loop['iteration'] - 1;
        $loop['first'] = ((int) $loop['iteration'] === 1);

        if (isset($loop['count'])) {
            $loop['remaining']--;
            $loop['last'] = ((int) $loop['iteration'] === (int) $loop['count']);
        }
    }

    /**
     * Get an instance of the first loop in the stack.
     *
     * @return \stdClass|null
     */
    public function getFirstLoop(): \stdClass|null
    {
        return ($last = end($this->loopStacks)) ? (object) $last : null;
    }
}
