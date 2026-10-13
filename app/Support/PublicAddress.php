<?php

namespace App\Support;

/**
 * Whether a host is on the public internet. Addresses the server reaches on
 * someone else's behalf (calendar links, push endpoints) must be, so that
 * nobody can make the server read its own network.
 */
class PublicAddress
{
    public static function isPublicHost(string $host): bool
    {
        $host = trim($host, '[]');

        if (filter_var($host, FILTER_VALIDATE_IP) !== false) {
            return self::isPublicIp($host);
        }

        $lower = strtolower($host);

        if (! str_contains($lower, '.') || str_ends_with($lower, '.local') || str_ends_with($lower, '.internal') || str_ends_with($lower, '.localhost')) {
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

    /**
     * An https address on the default port, without a login, to a public host.
     */
    public static function isPublicHttpsUrl(string $url): bool
    {
        $parts = parse_url($url);

        if (! is_array($parts) || strtolower($parts['scheme'] ?? '') !== 'https' || empty($parts['host'])
            || isset($parts['user']) || isset($parts['pass']) || (isset($parts['port']) && $parts['port'] !== 443)) {
            return false;
        }

        return self::isPublicHost($parts['host']);
    }

    private static function isPublicIp(string $ip): bool
    {
        $publicOnly = FILTER_FLAG_GLOBAL_RANGE | FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;

        // The address back means valid and public, false means not.
        return filter_var($ip, FILTER_VALIDATE_IP, $publicOnly) === $ip;
    }
}
