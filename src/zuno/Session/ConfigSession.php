<?php

namespace Zuno\Session;

use Zuno\Config\Config;
use RuntimeException;

class ConfigSession
{
    public static function configAppSession(): void
    {
        if (session_status() === PHP_SESSION_NONE && !session_start()) {
            throw new \Exception("Failed to start session.");
        }

        // Ensure no output has been sent
        if (headers_sent($file, $line)) {
            throw new \RuntimeException("Headers already sent in {$file} on line {$line}");
        }

        Config::initialize();

        $sessionConfig = (array) config('session');
        if (php_sapi_name() === 'cli' || defined('STDIN')) {
            return;
        }

        $sessionLifetime = (int) $sessionConfig['lifetime'];
        $sessionPath = $sessionConfig['path'];
        $sessionDomain = $sessionConfig['domain'] ?? ($_SERVER['HTTP_HOST'] ?? '');
        $sessionDriver = $sessionConfig['driver'];
        $sessionCookieName = $sessionConfig['cookie'];
        $sessionSecureCookie = (bool) ($sessionConfig['secure'] ?? isset($_SERVER['HTTPS']));
        $sessionHttpOnly = (bool) $sessionConfig['http_only'];
        $sessionSameSite = $sessionConfig['same_site'] ?? 'Lax';
        $sessionExpireOnClose = (bool) $sessionConfig['expire_on_close'];
        $sessionFiles = $sessionConfig['files'];

        // Validate lifetime
        if ($sessionLifetime <= 0) {
            throw new \InvalidArgumentException("Session lifetime must be a positive integer.");
        }

        // Set session driver
        if ($sessionDriver === 'file') {
            if (!is_dir($sessionFiles)) {
                if (!mkdir($sessionFiles, 0700, true)) {
                    throw new \RuntimeException("Failed to create session directory: {$sessionFiles}");
                }
            }
            ini_set('session.save_handler', 'files');
            ini_set('session.save_path', $sessionFiles);
        } elseif ($sessionDriver === 'cookie') {
            self::configureCookieSession([
                'lifetime' => $sessionLifetime * 60,
                'path' => $sessionPath,
                'domain' => $sessionDomain,
                'secure' => $sessionSecureCookie,
                'http_only' => $sessionHttpOnly,
                'same_site' => $sessionSameSite,
                'expire_on_close' => $sessionExpireOnClose,
            ]);
        } else {
            throw new \InvalidArgumentException("Unsupported session driver: {$sessionDriver}");
        }

        // Set session cookie parameters
        session_set_cookie_params([
            'lifetime' => $sessionExpireOnClose ? 0 : $sessionLifetime * 60,
            'path' => $sessionPath,
            'domain' => $sessionDomain,
            'secure' => $sessionSecureCookie,
            'httponly' => $sessionHttpOnly,
            'samesite' => $sessionSameSite
        ]);

        session_name($sessionCookieName);
        ini_set('session.gc_maxlifetime', $sessionLifetime * 60);

        // Clear invalid cookies before starting
        if (isset($_COOKIE[$sessionCookieName])) {
            try {
                if ($sessionDriver === 'cookie' && empty(self::decryptCookie($_COOKIE[$sessionCookieName]))) {
                    self::destroyCookie($sessionCookieName, $sessionPath, $sessionDomain);
                }
            } catch (\Exception $e) {
                error_log("Session cookie validation failed: " . $e->getMessage());
                self::destroyCookie($sessionCookieName, $sessionPath, $sessionDomain);
            }
        }

        // Regenerate session ID periodically
        if (
            !isset($_SESSION['last_regenerated']) ||
            (time() - $_SESSION['last_regenerated']) > ($sessionLifetime * 60)
        ) {
            self::regenerateSession();
            $_SESSION['last_regenerated'] = time();
        }

        // Generate CSRF token if not exists
        if (!isset($_SESSION['_token'])) {
            $_SESSION['_token'] = bin2hex(random_bytes(32));
        }
    }

    private static function regenerateSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    private static function configureCookieSession(array $config): void
    {
        ini_set('session.save_handler', 'user');
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_httponly', $config['http_only'] ? 1 : 0);
        ini_set('session.cookie_secure', $config['secure'] ? 1 : 0);
        ini_set('session.cookie_samesite', $config['same_site']);
        ini_set('session.cookie_path', $config['path']);
        ini_set('session.cookie_domain', $config['domain']);
        ini_set('session.cookie_lifetime', $config['expire_on_close'] ? 0 : $config['lifetime']);

        session_set_save_handler(
            [self::class, 'cookieOpen'],
            [self::class, 'cookieClose'],
            [self::class, 'cookieRead'],
            [self::class, 'cookieWrite'],
            [self::class, 'cookieDestroy'],
            [self::class, 'cookieGc']
        );
    }

    public static function cookieOpen($savePath, $sessionName): bool
    {
        return true;
    }

    public static function cookieClose(): bool
    {
        return true;
    }

    public static function cookieRead($sessionId): string
    {
        if (!isset($_COOKIE[session_name()])) {
            return '';
        }

        try {
            $data = self::decryptCookie($_COOKIE[session_name()]);
            if (empty($data)) {
                throw new \RuntimeException('Empty decrypted session data');
            }
            return $data;
        } catch (\Exception $e) {
            error_log("Session read error: " . $e->getMessage());
            self::destroyCookie(session_name(), session_get_cookie_params()['path'], session_get_cookie_params()['domain']);
            return '';
        }
    }

    public static function cookieWrite($sessionId, $sessionData): bool
    {
        if (empty($sessionData)) {
            return true;
        }

        try {
            $params = session_get_cookie_params();

            // Encrypt the session data
            $encrypted = self::encryptCookie($sessionData);

            // Set the cookie with all parameters
            $result = setcookie(
                session_name(),
                $encrypted,
                [
                    'expires' => $params['lifetime'] ? time() + $params['lifetime'] : 0,
                    'path' => $params['path'],
                    'domain' => $params['domain'],
                    'secure' => $params['secure'],
                    'httponly' => $params['httponly'],
                    'samesite' => $params['samesite'],
                ]
            );

            if (!$result) {
                error_log("Failed to set session cookie");
                return false;
            }

            // Update the $_COOKIE superglobal immediately
            $_COOKIE[session_name()] = $encrypted;
            return true;
        } catch (\Exception $e) {
            error_log("Session write error: " . $e->getMessage());
            return false;
        }
    }

    public static function cookieDestroy($sessionId): bool
    {
        $params = session_get_cookie_params();
        return self::destroyCookie(session_name(), $params['path'], $params['domain']);
    }

    public static function cookieGc($maxlifetime): bool
    {
        return true;
    }

    private static function destroyCookie($name, $path, $domain): bool
    {
        if (headers_sent()) {
            return false;
        }

        setcookie($name, '', [
            'expires' => time() - 3600,
            'path' => $path,
            'domain' => $domain,
            'secure' => !empty($_SERVER['HTTPS']),
            'httponly' => true,
        ]);

        unset($_COOKIE[$name]);
        return true;
    }

    private static function encryptCookie($data): string
    {
        $key = config('app.key');
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    private static function decryptCookie($data): string
    {
        $key = config('app.key');
        $data = base64_decode($data);
        $ivSize = openssl_cipher_iv_length('aes-256-cbc');
        $iv = substr($data, 0, $ivSize);
        $encrypted = substr($data, $ivSize);
        return openssl_decrypt($encrypted, 'aes-256-cbc', $key, 0, $iv);
    }
}
