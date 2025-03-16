<?php

namespace Zuno\Database;

use PDO;
use Zuno\Database\Builder;

class DB extends Builder
{
    /**
     * Set up and return a PDO connection instance.
     *
     * This method reads database configuration from environment variables,
     * constructs a DSN (Data Source Name) for connecting to a MySQL database,
     * and returns a new PDO instance configured with appropriate error handling
     * and default fetch mode.
     *
     * @return PDO The PDO connection instance.
     */
    public function getPdoInstance(): PDO
    {
        return Database::getPdoInstance();
    }
}
