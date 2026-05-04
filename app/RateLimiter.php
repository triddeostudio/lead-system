<?php

declare(strict_types=1);

final class RateLimiter
{
    public static function allow(string $key, int $maxAttempts, int $windowSeconds): bool
    {
        $root = dirname(__DIR__);
        $dir = $root . '/storage/ratelimit';

        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }

        $file = $dir . '/' . hash('sha256', $key) . '.json';
        $now = time();
        $events = [];

        if (is_file($file)) {
            $decoded = json_decode((string) file_get_contents($file), true);
            if (is_array($decoded)) {
                $events = array_values(array_filter($decoded, static fn ($timestamp) => is_int($timestamp) && $timestamp > ($now - $windowSeconds)));
            }
        }

        if (count($events) >= $maxAttempts) {
            return false;
        }

        $events[] = $now;
        @file_put_contents($file, json_encode($events), LOCK_EX);

        return true;
    }
}
