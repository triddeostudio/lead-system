<?php

declare(strict_types=1);

final class LeadValidator
{
    public static function validate(array $input): array
    {
        $errors = [];

        $name = Security::cleanString($input['name'] ?? $input['nombre'] ?? null, 200);
        $email = Security::cleanString($input['email'] ?? null, 255);
        $phone = Security::cleanString($input['phone'] ?? $input['telefono'] ?? null, 50);
        $company = Security::cleanString($input['company'] ?? $input['empresa'] ?? null, 200);
        $clientWebsite = Security::cleanString($input['website'] ?? $input['client_website'] ?? $input['website_url'] ?? $input['web'] ?? null, 500);
        $message = Security::cleanString($input['message'] ?? $input['mensaje'] ?? null, 5000);

        if (!$email && !$phone) {
            $errors['contact'] = 'Introduce email o teléfono.';
        }

        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El email no es válido.';
        }

        if ($phone && !preg_match('/^[0-9+().\s-]{6,50}$/', $phone)) {
            $errors['phone'] = 'El teléfono no es válido.';
        }

        if ($errors !== []) {
            return [false, $errors, []];
        }

        return [true, [], [
            'name' => $name,
            'email' => $email,
            'phone' => $phone,
            'company' => $company,
            'client_website' => $clientWebsite,
            'message' => $message,
            'source_site' => Security::cleanString($input['source_site'] ?? $_SERVER['HTTP_ORIGIN'] ?? null, 255),
            'source_url' => Security::cleanString($input['source_url'] ?? null, 1000),
            'form_name' => Security::cleanString($input['form_name'] ?? 'contacto', 120),
            'utm_source' => Security::cleanString($input['utm_source'] ?? null, 255),
            'utm_medium' => Security::cleanString($input['utm_medium'] ?? null, 255),
            'utm_campaign' => Security::cleanString($input['utm_campaign'] ?? null, 255),
            'utm_term' => Security::cleanString($input['utm_term'] ?? null, 255),
            'utm_content' => Security::cleanString($input['utm_content'] ?? null, 255),
            'referrer' => Security::cleanString($input['referrer'] ?? $_SERVER['HTTP_REFERER'] ?? null, 1000),
            'ip_address' => Security::clientIp(),
            'user_agent' => Security::cleanString($_SERVER['HTTP_USER_AGENT'] ?? null, 1000),
            'status' => 'nuevo',
            'priority' => self::detectPriority($message, $input),
            'consent' => Security::boolFromInput($input['consent'] ?? $input['privacidad'] ?? false),
            'spam_score' => 0,
            'raw_payload' => $input,
        ]];
    }

    private static function detectPriority(?string $message, array $input): string
    {
        $explicit = Security::cleanString($input['priority'] ?? null, 20);
        if (in_array($explicit, ['baja', 'media', 'alta', 'urgente'], true)) {
            return $explicit;
        }

        $haystack = mb_strtolower((string) $message);
        foreach (['urgente', 'hoy', 'inmediato', 'presupuesto urgente'] as $word) {
            if (str_contains($haystack, $word)) {
                return 'alta';
            }
        }

        return 'media';
    }
}
