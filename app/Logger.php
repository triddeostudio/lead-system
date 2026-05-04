<?php

declare(strict_types=1);

final class Logger
{
    public static function error(string $message, array $context = []): void
    {
        self::write('ERROR', $message, $context);
    }

    public static function info(string $message, array $context = []): void
    {
        self::write('INFO', $message, $context);
    }

    private static function write(string $level, string $message, array $context = []): void
    {
        $root = dirname(__DIR__);
        $logDir = $root . '/storage/logs';

        if (!is_dir($logDir)) {
            @mkdir($logDir, 0775, true);
        }

        $safeContext = self::redact($context);
        $line = json_encode([
            'time' => date('c'),
            'level' => $level,
            'message' => $message,
            'context' => $safeContext,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($line !== false) {
            @file_put_contents($logDir . '/app.log', $line . PHP_EOL, FILE_APPEND | LOCK_EX);
        }
    }

    private static function redact(array $context): array
    {
        $sensitive = ['password', 'token', 'secret', 'api_key', 'authorization'];

        foreach ($context as $key => $value) {
            foreach ($sensitive as $needle) {
                if (str_contains(strtolower((string) $key), $needle)) {
                    $context[$key] = '[redacted]';
                    continue 2;
                }
            }

            if (is_array($value)) {
                $context[$key] = self::redact($value);
            }
        }

        return $context;
    }
}
