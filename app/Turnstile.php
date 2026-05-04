<?php

declare(strict_types=1);

final class Turnstile
{
    public static function verify(?string $token, string $ip): bool
    {
        $secret = (string) Config::get('TURNSTILE_SECRET_KEY', '');

        if ($secret === '') {
            return true;
        }

        if (!$token) {
            return false;
        }

        if (!function_exists('curl_init')) {
            Logger::error('cURL is required for Turnstile verification.');
            return false;
        }

        $ch = curl_init('https://challenges.cloudflare.com/turnstile/v0/siteverify');
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 8,
            CURLOPT_POSTFIELDS => http_build_query([
                'secret' => $secret,
                'response' => $token,
                'remoteip' => $ip,
            ]),
        ]);

        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            Logger::error('Turnstile request failed.', ['error' => $error]);
            return false;
        }

        $decoded = json_decode($response, true);
        return is_array($decoded) && ($decoded['success'] ?? false) === true;
    }
}
