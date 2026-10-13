<?php

namespace App\Calendar;

/**
 * An iCal link a parent pasted. webcal:// (iPhone) is fetched as https://.
 * Only https to public addresses, so that a link can never make the server
 * read its own network.
 */
class IcsLink
{
    public static function normalize(string $link): string
    {
        $link = trim($link);

        return (string) preg_replace('#^webcals?://#i', 'https://', $link);
    }

    /**
     * Whether the server may fetch the link.
     */
    public static function isAllowed(string $link): bool
    {
        $parts = parse_url(self::normalize($link));

        if (! is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || (isset($parts['port']) && $parts['port'] !== 443)) {
            return false;
        }

        return self::isPublicHost($parts['host']);
    }

    public static function isPublicHost(string $host): bool
    {
        $host = trim($host, '[]');

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return self::isPublicIp($host);
        }

        if (! str_contains($host, '.') || str_ends_with(strtolower($host), '.local') || str_ends_with(strtolower($host), '.internal')) {
            return false;
        }

        $addresses = array_merge(
            array_column(@dns_get_record($host, DNS_A) ?: [], 'ip'),
            array_column(@dns_get_record($host, DNS_AAAA) ?: [], 'ipv6'),
        );

        if ($addresses === []) {
            return false;
        }

        foreach ($addresses as $address) {
            if (! self::isPublicIp((string) $address)) {
                return false;
            }
        }

        return true;
    }

    private static function isPublicIp(string $ip): bool
    {
        $publicOnly = FILTER_FLAG_GLOBAL_RANGE | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

        // The address back means valid and public, false means not.
        return filter_var($ip, FILTER_VALIDATE_IP, $publicOnly) === $ip;
    }
}
