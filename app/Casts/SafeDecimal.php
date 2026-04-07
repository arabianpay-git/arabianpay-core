<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Like {@see \Illuminate\Database\Eloquent\Casts\AsDecimal} but never throws on
 * null, empty string, or non-numeric legacy rows (returns "0.00" instead).
 */
class SafeDecimal implements CastsAttributes
{
    public function __construct(
        private int $scale = 2
    ) {}

    public function get(Model $model, string $key, mixed $value, array $attributes): string
    {
        return $this->normalizeToString($value);
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): string
    {
        return $this->normalizeToString($value);
    }

    private function normalizeToString(mixed $value): string
    {
        if ($value === null || $value === '') {
            return number_format(0.0, $this->scale, '.', '');
        }

        if (! is_numeric($value)) {
            return number_format(0.0, $this->scale, '.', '');
        }

        return number_format((float) $value, $this->scale, '.', '');
    }
}
