<?php

namespace Zuno\Session;

class FlashMessage
{
    private static $messages = [];

    /**
     * Add a flash message.
     *
     * @param string $type The type of message (e.g., success, error, warning, info).
     * @param string $message The message content.
     */
    public function message(string $type, string $message): void
    {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }

        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message,
        ];
    }

    /**
     * Retrieve and display flash messages.
     *
     * @return array An array of flash messages.
     */
    public static function allMessage(): array
    {
        if (isset($_SESSION['flash_messages'])) {
            $messages = $_SESSION['flash_messages'];
            unset($_SESSION['flash_messages']); // Clear messages after retrieval
            return $messages;
        }

        return [];
    }

    /**
     * Display flash messages as snackbars, with different colors per type.
     *
     * @return string HTML output of snackbars.
     */
    public static function display(): string
    {
        $messages = self::allMessage();
        $output = '';

        foreach ($messages as $message) {
            if (is_array($message) && isset($message['type']) && isset($message['message'])) {
                $alertType = match ($message['type']) {
                    'success' => 'success',
                    'error' => 'danger',
                    'warning' => 'warning',
                    'info' => 'info',
                    default => 'primary',
                };

                $output .= '<div class="alert alert-' . htmlspecialchars($alertType) . ' alert-dismissible fade show" role="alert">'
                    . htmlspecialchars($message['message'])
                    . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
                    . '</div>';

                if ($message['type'] === 'success') {
                    Input::clear();
                }
            } elseif (is_string($message)) {
                // Handle plain string messages as error alerts
                $output .= '<div class="alert alert-danger alert-dismissible fade show" role="alert">'
                    . htmlspecialchars($message)
                    . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>'
                    . '</div>';
            }
        }

        return $output;
    }

    /**
     * Add a flash message with a specific key.
     *
     * @param string $key The key for the message.
     * @param string $message The message content.
     */
    public static function set(string $key, string $message): void
    {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }

        $_SESSION['flash_messages'][$key] = $message;
    }

    /**
     * Get a specific flash message by key and remove it from the session.
     *
     * @param string $key The key of the message.
     * @return string|null The message content, or null if not found.
     */
    public static function get(string $key): ?string
    {
        if (isset($_SESSION['flash_messages'][$key])) {
            $message = $_SESSION['flash_messages'][$key];
            unset($_SESSION['flash_messages'][$key]);
            return $message;
        }

        return null;
    }

    /**
     * Peek at a specific flash message by key without removing it from the session.
     *
     * @param string $key The key of the message.
     * @return string|null The message content, or null if not found.
     */
    public static function peek(string $key): ?string
    {
        return $_SESSION['flash_messages'][$key] ?? null;
    }

    /**
     * Check if a specific flash message exists by key.
     *
     * @param string $key The key of the message.
     * @return bool True if the message exists, false otherwise.
     */
    public static function has(string $key): bool
    {
        return isset($_SESSION['flash_messages'][$key]);
    }

    /**
     * Check if there are any flash messages.
     *
     * @return bool True if there are flash messages, false otherwise.
     */
    public function hasMessages(): bool
    {
        return isset($_SESSION['flash_messages']) && !empty($_SESSION['flash_messages']);
    }
}
