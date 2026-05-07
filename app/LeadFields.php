<?php

declare(strict_types=1);

final class LeadFields
{
    private const STANDARD_FIELDS = [
        'name', 'nombre',
        'email',
        'phone', 'telefono',
        'company', 'empresa',
        'website', 'client_website', 'website_url', 'web',
        'message', 'mensaje',
        'source_site', 'source_url', 'source_path', 'page_title', 'form_name', 'referrer',
        'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content',
        'consent', 'privacidad',
        'priority', 'status',
    ];

    private const INTERNAL_FIELDS = [
        '_hp_website', 'hp_field',
        'cf-turnstile-response', 'turnstile_token',
        'csrf_token',
    ];

    private const LABELS = [
        'process' => 'Proceso',
        'proceso' => 'Proceso',
        'service' => 'Servicio',
        'servicio' => 'Servicio',
        'budget' => 'Presupuesto',
        'presupuesto' => 'Presupuesto',
        'city' => 'Ciudad',
        'ciudad' => 'Ciudad',
        'province' => 'Provincia',
        'provincia' => 'Provincia',
        'business_type' => 'Tipo de negocio',
        'tipo_negocio' => 'Tipo de negocio',
        'employees' => 'Nº empleados',
        'num_employees' => 'Nº empleados',
        'preferred_date' => 'Fecha preferida',
        'fecha_preferida' => 'Fecha preferida',
        'interest' => 'Interés',
        'interes' => 'Interés',
    ];

    public static function cleanRawPayload(array $input): array
    {
        $clean = [];

        foreach ($input as $key => $value) {
            $key = self::cleanKey((string) $key);
            if ($key === '' || self::isInternal($key)) {
                continue;
            }

            $clean[$key] = self::cleanValue($value);
        }

        return $clean;
    }

    public static function extractExtraFieldsFromRawPayload(mixed $rawPayload): array
    {
        if (is_string($rawPayload)) {
            $decoded = json_decode($rawPayload, true);
            $payload = is_array($decoded) ? $decoded : [];
        } elseif (is_array($rawPayload)) {
            $payload = $rawPayload;
        } else {
            $payload = [];
        }

        return self::extractExtraFields($payload);
    }

    public static function extractExtraFields(array $payload): array
    {
        $extra = [];

        foreach ($payload as $key => $value) {
            $key = self::cleanKey((string) $key);
            if ($key === '' || self::isStandard($key) || self::isInternal($key)) {
                continue;
            }

            $value = self::cleanValue($value);
            if (self::isEmptyValue($value)) {
                continue;
            }

            $extra[$key] = [
                'label' => self::label($key),
                'value' => $value,
            ];
        }

        return $extra;
    }

    public static function label(string $key): string
    {
        $key = self::cleanKey($key);
        if (isset(self::LABELS[$key])) {
            return self::LABELS[$key];
        }

        $label = str_replace(['_', '-'], ' ', $key);
        $label = preg_replace('/\s+/', ' ', $label) ?: $label;

        return mb_convert_case(trim($label), MB_CASE_TITLE, 'UTF-8');
    }

    public static function valueToString(mixed $value): string
    {
        if ($value === null) {
            return '';
        }

        if (is_bool($value)) {
            return $value ? 'sí' : 'no';
        }

        if (is_scalar($value)) {
            return trim((string) $value);
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
    }

    public static function extraFieldsToPlainArray(array $extraFields): array
    {
        $plain = [];
        foreach ($extraFields as $key => $field) {
            $plain[$key] = $field['value'] ?? null;
        }

        return $plain;
    }

    private static function isStandard(string $key): bool
    {
        return in_array($key, self::STANDARD_FIELDS, true);
    }

    private static function isInternal(string $key): bool
    {
        return in_array($key, self::INTERNAL_FIELDS, true);
    }

    private static function cleanKey(string $key): string
    {
        $key = trim($key);
        $key = preg_replace('/[^a-zA-Z0-9_\-]/', '', $key) ?? '';

        return mb_strtolower($key, 'UTF-8');
    }

    private static function cleanValue(mixed $value): mixed
    {
        if (is_array($value)) {
            $clean = [];
            foreach ($value as $key => $item) {
                $clean[$key] = self::cleanValue($item);
            }
            return $clean;
        }

        if (is_bool($value) || is_int($value) || is_float($value) || $value === null) {
            return $value;
        }

        return Security::cleanString((string) $value, 5000);
    }

    private static function isEmptyValue(mixed $value): bool
    {
        if ($value === null) {
            return true;
        }

        if (is_string($value)) {
            return trim($value) === '';
        }

        if (is_array($value)) {
            return $value === [];
        }

        return false;
    }
}
