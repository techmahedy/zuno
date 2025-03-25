<?php

namespace Zuno\Session\Handlers;

use Zuno\Session\Contracts\AbstractSessionHandler;
use RuntimeException;

class FileSessionHandler extends AbstractSessionHandler
{
    public function initialize(): void
    {
        $this->ensureSessionDirectoryExists();
        $this->configureFileSession();
    }

    public function start(): void
    {
        if (session_status() === PHP_SESSION_NONE && !session_start()) {
            throw new RuntimeException("Failed to start session.");
        }

        if ($this->shouldRegenerate()) {
            $this->regenerate();
            $_SESSION['last_regenerated'] = time();
        }

        $this->generateToken();
    }

    private function ensureSessionDirectoryExists(): void
    {
        if (!is_dir($this->config['files'])) {
            if (!mkdir($this->config['files'], 0700, true)) {
                throw new RuntimeException("Failed to create session directory: {$this->config['files']}");
            }
        }
    }

    private function configureFileSession(): void
    {
        @ini_set('session.save_handler', 'files');
        @ini_set('session.save_path', $this->config['files']);
        @ini_set('session.gc_maxlifetime', $this->config['lifetime'] * 60);
    }

    public function validate(): void {}
}
