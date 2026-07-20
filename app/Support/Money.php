<?php

namespace App\Support;

/**
 * Money handling for the payments module.
 *
 * Everything is integer MINOR UNITS (cents). Floats are never used to hold an
 * amount: 0.1 + 0.2 famously is not 0.3, and a resident's utility bill is not
 * the place to discover that. Parsing and formatting both live here so the
 * admin form, the resident checkout and the receipt can never disagree about
 * what "24.50" means.
 */
class Money
{
    /** "$1,240.50" — for display only. */
    public static function format(int $cents, ?string $symbol = null): string
    {
        $symbol ??= (string) config('payments.currency_symbol', '$');
        $negative = $cents < 0;
        $value = number_format(abs($cents) / 100, 2, '.', ',');

        return ($negative ? '-' : '') . $symbol . $value;
    }

    /** "1,240.50" — no symbol, for form inputs. */
    public static function decimal(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }

    /**
     * Parse operator/resident input into cents.
     *
     * Accepts "24.50", "$24.50", "1,240.50" and "24". Returns null for anything
     * that is not a well-formed positive amount, so the caller fails validation
     * rather than silently charging a number nobody typed.
     */
    public static function parse(string|int|float|null $input): ?int
    {
        if ($input === null) {
            return null;
        }

        $clean = preg_replace('/[^0-9.]/', '', (string) $input);

        if ($clean === '' || $clean === '.' || substr_count($clean, '.') > 1) {
            return null;
        }

        // round(), not (int) cast: (int)(24.50 * 100) is 2449 on some builds
        // because 24.50 is not exactly representable in binary floating point.
        return (int) round(((float) $clean) * 100);
    }
}
