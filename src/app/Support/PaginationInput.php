<?php

namespace App\Support;

class PaginationInput
{
    public static function normalize(
        mixed $value,
        int $default,
        int $max,
    ): int
    {
        if ($value === null) {
            return $default;
        }

        if (!is_string($value) && !is_int($value)) {
            return $default;
        }

        $value = (string)$value;

        if (!preg_match('/^[0-9]+$/', $value)) {
            return $default;
        }

        $normalized = filter_var($value, FILTER_VALIDATE_INT);

        if ($normalized === false) {
            return $max;
        }

        return min(max(1, $normalized), $max);
    }
}
