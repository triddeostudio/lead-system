<?php

declare(strict_types=1);

final class Time
{
    public static function timezone(): DateTimeZone
    {
        return new DateTimeZone((string) Config::get('APP_TIMEZONE', 'Europe/Madrid'));
    }

    public static function format(mixed $value, string $format = 'd/m/Y H:i'): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        try {
            $date = new DateTimeImmutable((string) $value);
            return $date->setTimezone(self::timezone())->format($format);
        } catch (Throwable) {
            return (string) $value;
        }
    }

    public static function datetimeLocal(mixed $value): string
    {
        return self::format($value, 'Y-m-d\TH:i');
    }

    public static function fromLocalInput(?string $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        try {
            $date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value, self::timezone());

            if (!$date) {
                return null;
            }

            return $date->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:sP');
        } catch (Throwable) {
            return null;
        }
    }
}
