<?php

namespace App\Helpers;

/**
 * [PHASE-2] Safe decimal arithmetic for financial operations.
 *
 * All monetary values are handled as string-based decimal arithmetic via bcmath
 * to avoid floating-point imprecision. Scale is 2 (halalat precision for SAR).
 */
class Money
{
    private const SCALE = 2;

    /**
     * Add two monetary values.
     */
    public static function add(string|float|int|null $a, string|float|int|null $b): string
    {
        return bcadd(self::normalize($a), self::normalize($b), self::SCALE);
    }

    /**
     * Subtract b from a.
     */
    public static function subtract(string|float|int|null $a, string|float|int|null $b): string
    {
        return bcsub(self::normalize($a), self::normalize($b), self::SCALE);
    }

    /**
     * Multiply a by b.
     */
    public static function multiply(string|float|int|null $a, string|float|int|null $b): string
    {
        return bcmul(self::normalize($a), self::normalize($b), self::SCALE);
    }

    /**
     * Divide a by b. Throws on division by zero.
     */
    public static function divide(string|float|int|null $a, string|float|int|null $b): string
    {
        $bNorm = self::normalize($b);
        if (bccomp($bNorm, '0', self::SCALE) === 0) {
            throw new \DivisionByZeroError('Division by zero in monetary calculation');
        }

        return bcdiv(self::normalize($a), $bNorm, self::SCALE);
    }

    /**
     * Return the maximum of a and b.
     */
    public static function max(string|float|int|null $a, string|float|int|null $b): string
    {
        $aNorm = self::normalize($a);
        $bNorm = self::normalize($b);

        return bccomp($aNorm, $bNorm, self::SCALE) >= 0 ? $aNorm : $bNorm;
    }

    /**
     * Compare two values. Returns -1, 0, or 1.
     */
    public static function compare(string|float|int|null $a, string|float|int|null $b): int
    {
        return bccomp(self::normalize($a), self::normalize($b), self::SCALE);
    }

    /**
     * Check if value is zero.
     */
    public static function isZero(string|float|int|null $value): bool
    {
        return bccomp(self::normalize($value), '0', self::SCALE) === 0;
    }

    /**
     * Check if value is positive (> 0).
     */
    public static function isPositive(string|float|int|null $value): bool
    {
        return bccomp(self::normalize($value), '0', self::SCALE) > 0;
    }

    /**
     * Normalize any numeric input to a string suitable for bcmath.
     */
    public static function normalize(string|float|int|null $value): string
    {
        if ($value === null || $value === '') {
            return '0.00';
        }

        return number_format((float) $value, self::SCALE, '.', '');
    }
}
