<?php

namespace App\Services;

/**
 * Minimal North American (+1) phone normalizer. v1 serves Ontario listing
 * agents, so non-NANP numbers are rejected instead of guessed at.
 */
final class PhoneNumber
{
    /**
     * @return string|null E.164 like +16135550123, or null if it is not a valid NANP number
     */
    public static function toE164(string $raw): ?string
    {
        $raw = trim($raw);

        if (str_starts_with($raw, '+') && ! str_starts_with($raw, '+1')) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $raw) ?? '';

        if (strlen($digits) === 11 && $digits[0] === '1') {
            $digits = substr($digits, 1);
        }

        if (strlen($digits) !== 10) {
            return null;
        }

        // NANP: area code and exchange cannot start with 0 or 1.
        if ($digits[0] < '2' || $digits[3] < '2') {
            return null;
        }

        return '+1'.$digits;
    }
}
