<?php

namespace Zuno\Auth\Security;

use Zuno\Support\Facades\Hash;
use App\Models\User;
use Zuno\Database\Eloquent\Model;

class Authenticate extends Model
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
     * @param bool $remember
     * @return bool
     */
    public function try(array $credentials = [], bool $remember = false): bool
    {
        $email = $credentials['email'] ?? '';
        $password = $credentials['password'] ?? '';

        if (empty($credentials['email']) || empty($credentials['password'])) {
            throw new \Exception("Email or Password not found", 1);
        }

        $user = User::query()
            ->where('email', '=', $email)
            ->first();

        if ($user && Hash::check($password, $user->password)) {
            self::setUser($user);

            if ($remember) {
                $this->setRememberToken($user);
            }

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

        // Check for remember token
        if (isset($_COOKIE['remember_token'])) {
            $user = User::query()
                ->where('remember_token', '=', $_COOKIE['remember_token'])
                ->first();
            if ($user) {
                self::setUser($user);
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
        if (isset($_COOKIE['remember_token'])) {
            setcookie('remember_token', '', time() - 3600, '/');
        }
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
     * Set the remember token for the user.
     *
     * @param \Zuno\Models\User $user
     */
    private function setRememberToken(User $user)
    {
        $token = bin2hex(random_bytes(30));
        $user->remember_token = $token;
        $user->save();

        setcookie('remember_token', $token, time() + 3600 * 24 * 30, '/');
    }

    /**
     * Determine if the user was logged in via remember me token.
     *
     * @return bool
     */
    public function viaRemember(): bool
    {
        return isset($_COOKIE['remember_token']);
    }
}
