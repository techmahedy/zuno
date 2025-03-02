<?php

namespace Zuno\Auth\Security;

class Hash
{
    /**
     * Hash a password using bcrypt.
     *
     * @param string $password
     * @return string
     */
    public static function make($password)
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verify if a given password matches the stored hash.
     *
     * @param string $password
     * @param string $hashedPassword
     * @return bool
     */
    public static function check($password, $hashedPassword)
    {
        return password_verify($password, $hashedPassword);
    }

    /**
     * Check if a password hash needs rehashing (security upgrade).
     *
     * @param string $hashedPassword
     * @return bool
     */
    public static function needsRehash($hashedPassword)
    {
        return password_needs_rehash($hashedPassword, PASSWORD_BCRYPT);
    }
}
