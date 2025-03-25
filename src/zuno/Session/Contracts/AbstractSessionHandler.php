<?php

namespace Zuno\Session\Contracts;

use Zuno\Session\Contracts\SessionHandlerInterface;

abstract class AbstractSessionHandler implements SessionHandlerInterface
{
    protected array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function generateToken(): void
    {
        if (!isset($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }
    }

    public function regenerate(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    protected function shouldRegenerate(): bool
    {
        return !isset($_SESSION['last_regenerated']) ||
            (time() - $_SESSION['last_regenerated']) > ($this->config['lifetime'] * 60);
    }
}
