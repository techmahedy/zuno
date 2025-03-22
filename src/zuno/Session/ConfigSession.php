<?php

namespace Zuno\Session;

use Zuno\Config\Config;
use Zuno\Http\Response\Cookie;

class ConfigSession
{
    public static function configAppSession(): void
    {
        Config::initialize();

        $sessionConfig = (array) config('session');
        if (php_sapi_name() === 'cli' || defined('STDIN')) {
            return;
        }

        $sessionLifetime = (int) $sessionConfig['lifetime'];
        $sessionPath = $sessionConfig['path'];
        $sessionDomain = $sessionConfig['domain'];
        $sessionDriver = $sessionConfig['driver'];
        $sessionCookieName = $sessionConfig['cookie'];
        $sessionSecureCookie = (bool) $sessionConfig['secure'];
        $sessionHttpOnly = (bool) $sessionConfig['http_only'];
        $sessionSameSite = $sessionConfig['same_site'];
        $sessionExpireOnClose = (bool) $sessionConfig['expire_on_close'];
        $sessionFiles = $sessionConfig['files'];

        // Validate lifetime
        if ($sessionLifetime <= 0) {
            throw new \Exception("Session lifetime must be a positive integer.");
        }

        // Set session driver
        if ($sessionDriver === 'file') {
            if (!is_dir($sessionFiles)) {
                if (!mkdir($sessionFiles, 0700, true)) {
                    throw new \Exception("Failed to create session directory: " . $sessionFiles);
                }
            }

            ini_set('session.save_path', $sessionFiles);

            if (ini_get('session.save_path') !== $sessionFiles) {
                throw new \Exception("Failed to set session save path: " . $sessionFiles);
            }
        } elseif ($sessionDriver === 'cookie') {
            self::configureCookieSession($sessionConfig);
        } else {
            throw new \Exception("Unsupported session driver: " . $sessionDriver);
        }

        // Set session cookie using the Cookie class
        $cookie = new Cookie(
            $sessionCookieName,
            '', // Value will be set by PHP
            $sessionExpireOnClose ? 0 : $sessionLifetime * 60,
            $sessionPath,
            $sessionDomain,
            $sessionSecureCookie,
            $sessionHttpOnly,
            false, // raw
            $sessionSameSite
        );

        // Set session cookie parameters
        session_set_cookie_params([
            'lifetime' => $cookie->getExpiresTime(),
            'path' => $cookie->getPath(),
            'domain' => $cookie->getDomain(),
            'secure' => $cookie->isSecure(),
            'httponly' => $cookie->isHttpOnly(),
            'samesite' => $cookie->getSameSite(),
        ]);

        // Start session with error handling
        if (session_status() === PHP_SESSION_NONE && !session_start()) {
            throw new \Exception("Failed to start session.");
        }

        // Regenerate after lifetime expires.
        if (isset($_SESSION['last_regenerated']) && (time() - $_SESSION['last_regenerated']) > ($sessionLifetime * 60)) {
            self::regenerateSession();
            $_SESSION['_token'] = bin2hex(openssl_random_pseudo_bytes(16));
            $_SESSION['last_regenerated'] = time();
        } elseif (!isset($_SESSION['last_regenerated'])) {
            $_SESSION['_token'] = bin2hex(openssl_random_pseudo_bytes(16));
            $_SESSION['last_regenerated'] = time();
        }
    }

    private static function regenerateSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }

    private static function configureCookieSession(array $sessionConfig): void
    {
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_httponly', $sessionConfig['http_only']);
        ini_set('session.cookie_secure', $sessionConfig['secure']);
        ini_set('session.cookie_samesite', $sessionConfig['same_site']);
        ini_set('session.cookie_path', $sessionConfig['path']);
        ini_set('session.cookie_domain', $sessionConfig['domain']);
        ini_set('session.cookie_lifetime', $sessionConfig['expire_on_close'] ? 0 : $sessionConfig['lifetime'] * 60);

        // Custom session handlers for cookie-based storage
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
        if (isset($_COOKIE[session_name()])) {
            return self::decryptCookie($_COOKIE[session_name()]);
        }
        return '';
    }

    public static function cookieWrite($sessionId, $sessionData): bool
    {
        $cookie = new Cookie(
            session_name(),
            self::encryptCookie($sessionData),
            session_get_cookie_params()['lifetime'] ? time() + session_get_cookie_params()['lifetime'] : 0,
            session_get_cookie_params()['path'],
            session_get_cookie_params()['domain'],
            session_get_cookie_params()['secure'],
            session_get_cookie_params()['httponly'],
            false, // raw
            session_get_cookie_params()['samesite']
        );

        setcookie(
            $cookie->getName(),
            $cookie->getValue(),
            [
                'expires' => $cookie->getExpiresTime(),
                'path' => $cookie->getPath(),
                'domain' => $cookie->getDomain(),
                'secure' => $cookie->isSecure(),
                'httponly' => $cookie->isHttpOnly(),
                'samesite' => $cookie->getSameSite(),
            ]
        );

        return true;
    }

    public static function cookieDestroy($sessionId): bool
    {
        $cookie = new Cookie(
            session_name(),
            '',
            time() - 3600,
            session_get_cookie_params()['path'],
            session_get_cookie_params()['domain'],
            session_get_cookie_params()['secure'],
            session_get_cookie_params()['httponly'],
            false, // raw
            session_get_cookie_params()['samesite']
        );

        setcookie(
            $cookie->getName(),
            $cookie->getValue(),
            [
                'expires' => $cookie->getExpiresTime(),
                'path' => $cookie->getPath(),
                'domain' => $cookie->getDomain(),
                'secure' => $cookie->isSecure(),
                'httponly' => $cookie->isHttpOnly(),
                'samesite' => $cookie->getSameSite(),
            ]
        );

        return true;
    }

    public static function cookieGc($maxlifetime): bool
    {
        return true; // No garbage collection needed for cookie-based sessions
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
