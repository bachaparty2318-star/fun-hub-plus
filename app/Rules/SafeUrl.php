<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SafeUrl implements ValidationRule
{
    public static function allowed(string $value): bool
    {
        if (preg_match('/[\x00-\x20\x7f\\\\]/', $value)) {
            return false;
        }
        if (str_starts_with($value, '/') && ! str_starts_with($value, '//')) {
            return ! str_contains(rawurldecode($value), '..');
        }

        return filter_var($value, FILTER_VALIDATE_URL)
            && in_array(strtolower(parse_url($value, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true)
            && ! parse_url($value, PHP_URL_USER);
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! self::allowed($value)) {
            $fail('The :attribute must be an HTTP(S) URL or a local absolute path.');
        }
    }
}
