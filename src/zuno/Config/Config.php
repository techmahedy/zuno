<?php

namespace Zuno\Config;

final class Config
{
    /**
     * Stores loaded configuration data.
     *
     * @var array<string, mixed>
     */
    protected static array $config = [];

    /**
     * Cache file path.
     *
     * @var string
     */
    protected static string $cacheFile;

    /**
     * Static method to initialize cacheFile path.
     */
    public static function initialize(): void
    {
        self::$cacheFile = base_path() . '/storage/cache/config.php';
    }

    /**
     * Dynamically get a configuration value or return null if not found.
     *
     * @param string $name
     * @return mixed|null
     */
    public function __get(string $name): mixed
    {
        return self::get($name);
    }

    /**
     * Load all configuration files.
     */
    public static function loadAll(): void
    {
        foreach (glob(base_path() . '/config/*.php') as $file) {
            $fileName = basename($file, '.php');
            self::$config[$fileName] = include $file;
        }
    }

    /**
     * Load configuration from the cache if available.
     */
    public static function loadFromCache(): void
    {
        if (file_exists(self::$cacheFile)) {
            self::$config = include self::$cacheFile;
        } else {
            // If cache doesn't exist, load from config files and cache
            self::loadAll();
            self::cacheConfig();
        }
    }

    /**
     * Cache the configuration to a file.
     */
    protected static function cacheConfig(): void
    {
        // Ensure the cache directory exists
        $cacheDir = dirname(self::$cacheFile);
        if (!is_dir($cacheDir)) {
            // Try creating the cache directory if it doesn't exist
            if (!mkdir($cacheDir, 0777, true) && !is_dir($cacheDir)) {
                throw new \RuntimeException(sprintf('Directory "%s" was not created', $cacheDir));
            }
        }

        $content = '<?php return ' . var_export(self::$config, true) . ';';
        file_put_contents(self::$cacheFile, $content);
    }

    /**
     * Get a configuration value by key.
     *
     * @param string $key The key to retrieve (dot notation).
     * @return mixed|null The configuration value or null if not found.
     */
    public static function get(string $key): mixed
    {
        $keys = explode('.', $key);
        $file = array_shift($keys);

        // If the file is not loaded, load it from config or cache
        if (!isset(self::$config[$file])) {
            self::loadFromCache();  // Ensure config is loaded (from cache or files)
        }

        // Traverse through the array using the keys
        $value = self::$config[$file] ?? null;
        foreach ($keys as $keyPart) {
            $value = $value[$keyPart] ?? null;
            if ($value === null) {
                break;
            }
        }

        return $value;
    }

    /**
     * Set a configuration value.
     *
     * @param string $key The key to set (dot notation).
     * @param mixed $value The value to set.
     */
    public static function set(string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $file = array_shift($keys);

        // If the file doesn't exist, initialize it
        if (!isset(self::$config[$file])) {
            self::$config[$file] = [];
        }

        // Traverse and set the value
        $configArray = &self::$config[$file];
        foreach ($keys as $keyPart) {
            if (!isset($configArray[$keyPart])) {
                $configArray[$keyPart] = [];
            }
            $configArray = &$configArray[$keyPart];
        }

        $configArray = $value;

        // After setting the value, we can cache the updated configuration
        self::cacheConfig();
    }

    /**
     * Get all the configuration settings.
     *
     * @return array<string, mixed> All configurations.
     */
    public static function all(): array
    {
        self::loadFromCache();
        return self::$config;
    }

    /**
     * Check if a configuration key exists.
     *
     * @param string $key The key to check (dot notation).
     * @return bool
     */
    public static function has(string $key): bool
    {
        return self::get($key) !== null;
    }

    /**
     * Clear the cached configuration file.
     */
    public static function clearCache(): void
    {
        if (file_exists(self::$cacheFile)) {
            unlink(self::$cacheFile);
        }
    }
}
