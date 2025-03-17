<?php

namespace Zuno\Http;

class ServerBag
{
    private array $server;

    public function __construct(array $server = [])
    {
        $this->server = $server;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->server[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return isset($this->server[$key]);
    }

    public function all(): array
    {
        return $this->server;
    }

    public function getHeaders(): array
    {
        $headers = [];
        foreach ($this->server as $key => $value) {
            if (str_starts_with($key, 'HTTP_')) {
                $headers[str_replace('_', '-', substr($key, 5))] = $value;
            }
        }

        return $headers;
    }
}
