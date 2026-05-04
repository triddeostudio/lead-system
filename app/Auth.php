<?php

declare(strict_types=1);

final class Auth
{
    public static function start(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_name('lead_admin_session');
            session_start();
        }
    }

    public static function login(string $username, string $password): bool
    {
        self::start();

        $expectedUser = (string) Config::get('ADMIN_USER', 'admin');
        $passwordHash = (string) Config::get('ADMIN_PASSWORD_HASH', '');
        $plainPassword = (string) Config::get('ADMIN_PASSWORD', '');

        $validUser = hash_equals($expectedUser, $username);
        $validPassword = false;

        if ($passwordHash !== '') {
            $validPassword = password_verify($password, $passwordHash);
        } elseif ($plainPassword !== '') {
            $validPassword = hash_equals($plainPassword, $password);
        }

        if ($validUser && $validPassword) {
            session_regenerate_id(true);
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_user'] = $username;
            return true;
        }

        return false;
    }

    public static function check(): bool
    {
        self::start();
        return ($_SESSION['admin_logged_in'] ?? false) === true;
    }

    public static function require(): void
    {
        if (!self::check()) {
            Response::redirect('/admin/login.php');
        }
    }

    public static function user(): string
    {
        self::start();
        return (string) ($_SESSION['admin_user'] ?? 'admin');
    }

    public static function logout(): void
    {
        self::start();
        $_SESSION = [];
        session_destroy();
    }
}
