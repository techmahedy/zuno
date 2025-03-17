<?php

namespace Zuno\Auth\Security;

use Zuno\Support\Facades\Hash;
use App\Models\User;

class Authenticate
{
    private $data = [];

    public function __set($name, $value)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        return $this->data[$name] ?? null;
    }

    /**
     * Attempt to log the user in with email and password.
     *
     * @param array $credentials
     * @return bool
     */
    public function try(array $credentials = []): bool
    {
        $email = $credentials['email'] ?? '';
        $password = $credentials['password'] ?? '';

        if (empty($credentials['email']) || empty($credentials['password'])) {
            throw new \Exception("Email or Password not found", 1);
        }

        $user = User::query()
            ->where('email', '=', $email)
            ->orWhere('username', '=', $email)
            ->first();

        if ($user && Hash::check($password, $user->password)) {
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
    public function user(): ?User
    {
        if (isset($_SESSION['user'])) {
            $user = User::find($_SESSION['user']->id);
            $reflectionProperty = new \ReflectionProperty(User::class, 'unexposable');
            $reflectionProperty->setAccessible(true);
            if ($user) {
                $user->makeHidden($reflectionProperty->getValue($user));
                return $user;
            }
        }

        return null;
    }

    /**
     * Check if the user is authenticated.
     *
     * @return bool
     */
    public function check()
    {
        return self::user() !== null;
    }

    /**
     * Log the user out by clearing the session or token.
     */
    public function logout()
    {
        unset($_SESSION['user']);
    }

    /**
     * Set the authenticated user in the session.
     *
     * @param \Zuno\Models\User $user
     */
    private function setUser(User $user)
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
    public function viaRemember(): bool
    {
        return false;
    }
}
