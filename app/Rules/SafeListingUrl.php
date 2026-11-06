<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * A listing link is texted to strangers and used as a redirect target, so it
 * must be a plain https link to a real hostname on someone else's site.
 */
class SafeListingUrl implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parts = parse_url((string) $value);
        $host = strtolower($parts['host'] ?? '');

        $ownHost = strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST));

        $bad = ($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass'])
            || ! str_contains($host, '.')
            || filter_var($host, FILTER_VALIDATE_IP) !== false
            || preg_match('/^[a-z0-9.-]+$/', $host) !== 1
            || ($ownHost !== '' && ($host === $ownHost || str_ends_with($host, '.'.$ownHost)));

        if ($bad) {
            $fail('Use a full https:// link to your own listing page (not an IP address, and not a scanin.ca link).');
        }
    }
}
