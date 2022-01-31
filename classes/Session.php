<?php

/**
 * Session class
 *
 * Avoid using php globals directly
 */
class Session
{
    public const REFERRER_NAME = 'wordpress_open_oauth_referrer';

    final public static function start(string $name = 'wordpress_open_oauth'): void
    {
        session_name($name);
        session_start();
    }

    final public static function unset(): void
    {
        session_unset();
    }

    final public static function get(string $key): string
    {
        return $_SESSION[$key];
    }

    final public static function set(string $key, string $value): void
    {
        $_SESSION[$key] = $value;
    }

    final public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }
}
