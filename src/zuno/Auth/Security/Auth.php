<?php

namespace Zuno\Auth\Security;

use App\Models\User;
use Zuno\Auth\Security\Hash;

trait Auth
{
    /**
     * Attempt to log the user in with email and password.
     *
     * @param array $credentials
     * @return bool
     */
    public static function establishSession(array $credentials = []): bool
    {
        $email = $credentials['email'] ?? '';
        $password = $credentials['password'] ?? '';

        if (empty($credentials['email']) || empty($credentials['password'])) {
            throw new \Exception("Email or Password not found", 1);
        }

        $user = User::where('email', $email)->orWhere('username', $email)->first();

        if ($user && Hash::check($password, $user->password)) {
            // Set the user as the authenticated user
            self::setUser($user);
            return true;
        }

        return false;
    }

    /**
     * Get the currently authenticated user.
     *
     * @return \Zuno\Models\User|null
     */
    public static function user()
    {
        if (isset($_SESSION['user'])) {
            return User::find($_SESSION['user']->id);
        }

        return  null;
    }

    /**
     * Check if the user is authenticated.
     *
     * @return bool
     */
    public static function check()
    {
        return self::user() !== null;
    }

    /**
     * Log the user out by clearing the session or token.
     */
    public static function logout()
    {
        unset($_SESSION['user']);
    }

    /**
     * Set the authenticated user in the session.
     *
     * @param \Zuno\Models\User $user
     */
    private static function setUser(User $user)
    {
        $_SESSION['user'] = $user;
    }

    /**
     * ! Todo: Future implementation
     * Determine if the user was logged in via remember me token.
     * This is a stub for now as it requires implementing remember token handling.
     *
     * @return bool
     */
    public static function viaRemember(): bool
    {
        return false;
    }
}
