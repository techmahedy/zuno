<?php

namespace Zuno;

class Session
{
    /**
     * Stores a value in the session for the next request only.
     *
     * @param string $key The session key.
     * @param mixed $value The value to store.
     * @return void
     */
    public function flash(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Retrieves and removes a value from the session.
     *
     * @param string $key The session key.
     * @return mixed The value from the session, or null if the key does not exist.
     */
    public function get(string $key): mixed
    {
        $message = $_SESSION[$key] ?? null;
        unset($_SESSION[$key]);

        return $message;
    }

    /**
     * Checks if a session key exists and has a value.
     *
     * @param string $key The session key.
     * @return mixed The value if it exists, or false if the key does not exist.
     */
    public function has(string $key): mixed
    {
        return $_SESSION[$key] ?? false;
    }
}
