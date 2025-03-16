<?php

namespace Zuno\Database;

use Phinx\Config\Config;
use PDO;

class Database
{
    public static function getPdoInstance(): PDO
    {
        // Retrieve database configuration from environment variables
        $host = $_ENV['DB_HOST'];
        $dbName = $_ENV['DB_DATABASE'];
        $dsn = "mysql:host=$host;dbname=$dbName;charset=utf8";
        $username = $_ENV['DB_USERNAME'];
        $password = $_ENV['DB_PASSWORD'];

        // Create and return a new PDO instance with error handling and fetch mode settings
        return new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Enable exception mode for errors
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch results as associative arrays
        ]);
    }
}
