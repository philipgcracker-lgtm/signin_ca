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
        // A trailing dot is a valid, equivalent hostname ("scanin.ca."); drop it before comparing.
        $host = rtrim(strtolower($parts['host'] ?? ''), '.');

        $ownHost = rtrim(strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST)), '.');

        $bad = ($parts['scheme'] ?? '') !== 'https'
            || isset($parts['user']) || isset($parts['pass'])
            || ! str_contains($host, '.')
            || filter_var($host, FILTER_VALIDATE_IP) !== false
            || preg_match('/^[a-z0-9.-]+$/', $host) !== 1
            // The last label (the TLD) must contain a letter: blocks short IPs like 127.1 and 10.1.
            || preg_match('/[a-z]/', substr($host, (int) strrpos($host, '.') + 1)) !== 1
            || str_contains($host, '..')
            || ($ownHost !== '' && ($host === $ownHost || str_ends_with($host, '.'.$ownHost)));

        if ($bad) {
            $fail('Use a full https:// link to your own listing page (not an IP address, and not a scanin.ca link).');
        }
    }
}
