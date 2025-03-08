<?php

namespace Zuno\Support;

class Session
{
    /**
     * The session data.
     *
     * @var array
     */
    protected $data = [];

    /**
     * Get a session value by key.
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Put a key-value pair into the session.
     *
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function put(string $key, $value): void
    {
        $this->data[$key] = $value;
        $_SESSION[$key] = $value;
    }

    /**
     * Check if a session key exists.
     *
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        return isset($this->data[$key]);
    }

    /**
     * Remove a session key.
     *
     * @param string $key
     * @return void
     */
    public function forget(string $key): void
    {
        unset($this->data[$key]);

        unset($_SESSION[$key]);
    }

    /**
     * Remove all session data.
     *
     * @return void
     */
    public function flush(): void
    {
        $this->data = [];

        $_SESSION = [];
    }

    /**
     * Regenerate the session ID.
     *
     * @return void
     */
    public function regenerate(): void
    {
        session_regenerate_id(true);
    }

    /**
     * Get all session data.
     *
     * @return array
     */
    public function all(): array
    {
        return $_SESSION;
    }

    /**
     * Get the session ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return session_id();
    }

    /**
     * Set the session ID.
     *
     * @param string $id
     * @return void
     */
    public function setId(string $id): void
    {
        session_id($id);
    }

    /**
     * Destroy the session.
     *
     * @return void
     */
    public function destroy(): void
    {
        session_destroy();

        $this->data = [];
    }
}
