<?php

namespace App\Support;

final class CvPdfLink
{
    private const SCHEMES = ['http', 'https', 'mailto', 'tel'];

    public static function href(?string $value): ?string
    {
        $value = self::clean($value);

        if ($value === null || str_starts_with($value, '//')) {
            return null;
        }

        $parts = parse_url($value);

        if ($parts === false || ! isset($parts['scheme'])) {
            return null;
        }

        $scheme = strtolower($parts['scheme']);

        if (! in_array($scheme, self::SCHEMES, true)) {
            return null;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        if ($scheme === 'http' || $scheme === 'https') {
            if (($parts['host'] ?? '') === '' || ! filter_var($value, FILTER_VALIDATE_URL)) {
                return null;
            }

            return $value;
        }

        $target = $parts['path'] ?? '';

        if ($scheme === 'mailto') {
            return filter_var($target, FILTER_VALIDATE_EMAIL) ? $value : null;
        }

        $phone = self::normalizePhone($target);

        return $phone === null ? null : 'tel:'.$phone;
    }

    public static function emailHref(?string $email): ?string
    {
        $email = self::clean($email);

        if ($email === null || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return 'mailto:'.$email;
    }

    public static function phoneHref(?string $phone): ?string
    {
        $phone = self::clean($phone);

        if ($phone === null) {
            return null;
        }

        $normalized = self::normalizePhone($phone);

        return $normalized === null ? null : 'tel:'.$normalized;
    }

    private static function clean(?string $value): ?string
    {
        if ($value === null || preg_match('/[\x00-\x1F\x7F]|%0[0-9A-F]|%1[0-9A-F]|%7F/i', $value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '') {
            return null;
        }

        return $value;
    }

    private static function normalizePhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return null;
        }

        return (str_starts_with(trim($phone), '+') ? '+' : '').$digits;
    }
}
