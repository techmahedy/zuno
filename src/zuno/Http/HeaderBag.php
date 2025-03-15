<?php

namespace Zuno\Http;

class HeaderBag
{
    private array $headers;

    public function __construct(array $headers = [])
    {
        $this->headers = $headers;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->headers[$key] ?? $default;
    }

    public function set(string $key, mixed $value): void
    {
        $this->headers[$key] = $value;
    }

    public function has(string $key): bool
    {
        return isset($this->headers[$key]);
    }

    public function all(): array
    {
        return $this->headers;
    }

    public function replace(array $headers): void
    {
        $this->headers = $headers;
    }
}
