<?php

declare(strict_types=1);

namespace Radiant\Session;

use InvalidArgumentException;

final class Session
{
    /**
     * Prefix for flash messages stored in the session.
     */
    private const FLASH_PREFIX = 'flashed_';

    /**
     * Initializes the session if it is not already started.
     */
    public static function init()
    {
        if (session_id() === '') {
            session_start();
        }
    }

    /**
     * Sets a session variable with the specified key and value.
     *
     * @param  string  $key  The session variable name.
     * @param  mixed  $value  The value to store in the session.
     */
    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    /**
     * Sets a session variable with an expiration time.
     *
     * @param  string  $key  The session variable name.
     * @param  mixed  $value  The value to store in the session.
     * @param  int  $exp  Expiration time in seconds (default: 3600 seconds).
     *
     * @throws InvalidArgumentException If the expiration time is not positive.
     */
    public static function withExpire(string $key, mixed $value, int $exp = 3600): void
    {
        if ($exp <= 0) {
            throw new InvalidArgumentException('Expiration time must be a positive integer!');
        }

        $_SESSION[$key] = [
            'data' => $value,
            'exp' => time() + $exp,
        ];
    }

    /**
     * Retrieves a session variable by its key.
     *
     * @param  string  $key  The session variable name.
     * @param  mixed  $default  Default value if the variable is not set or expired.
     * @return mixed The stored value or the default value.
     */
    public static function get(string $key, $default = ''): mixed
    {
        return self::expired($key) ?? $default;
    }

    /**
     * Deletes one or more session variables.
     *
     * @param  string|array  $keys  The session variable name(s) to delete.
     */
    public static function delete(string|array $keys): void
    {
        foreach ((array) $keys as $key) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * Retrieves all session variables.
     *
     * @return array An associative array of all session variables.
     */
    public static function all(): array
    {
        return $_SESSION ? array_merge([], $_SESSION) : [];
    }

    /**
     * Destroys the current session and clears all session data.
     */
    public static function destroy(): void
    {
        if (session_id() !== '') {
            session_unset();
            session_destroy();

            if (ini_get('session.use_cookies')) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 420000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
            }
        }
    }

    /**
     * Counts the number of variables currently stored in the session.
     *
     * @return int The number of session variables.
     */
    public static function count(): int
    {
        return count($_SESSION);
    }

    /**
     * Sets a flash message in the session.
     *
     * @param  string  $value  The message to store.
     * @param  string  $key  The key to identify the flash message.
     */
    public static function flash(string $value, string $key): void
    {
        $_SESSION[self::FLASH_PREFIX.$key] = $value;
    }

    /**
     * Retrieves and removes a flash message from the session.
     *
     * @param  string  $key  The key identifying the flash message.
     * @param  mixed  $default  Default value if the flash message is not set.
     * @return mixed The flash message value or the default value.
     */
    public static function flashed(?string $key = null, $default = ''): mixed
    {
        if ($key !== null) {
            $data = $_SESSION[self::FLASH_PREFIX.$key] ?? $default;

            unset($_SESSION[self::FLASH_PREFIX.$key]);

            return $data;
        }

        $flashed = [];

        foreach ($_SESSION as $sessionKey => $value) {
            if (str_starts_with($sessionKey, self::FLASH_PREFIX)) {
                $originalKey = mb_substr($sessionKey, mb_strlen(self::FLASH_PREFIX));

                $flashed[$originalKey] = $value;

                unset($_SESSION[$sessionKey]);
            }
        }

        return $flashed;
    }

    /**
     * Retrieves the current session ID.
     *
     * @return string|null The session ID or null if no session is active.
     */
    public static function sessid(): ?string
    {
        return session_id();
    }

    /**
     * Checks if a session variable with an expiration time has expired.
     *
     * @param  string  $key  The session variable name.
     * @return mixed|null The stored value if not expired, or null if expired.
     */
    private static function expired(string $key): mixed
    {
        $data = $_SESSION[$key] ?? null;

        if (isset($data['exp']) && $data['exp'] < time()) {
            unset($_SESSION[$key]);

            return null;
        }

        return $data['data'] ?? $data;
    }
}
