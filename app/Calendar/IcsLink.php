<?php

namespace App\Calendar;

use App\Support\PublicAddress;

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
        return PublicAddress::isPublicHttpsUrl(self::normalize($link));
    }

    public static function isPublicHost(string $host): bool
    {
        return PublicAddress::isPublicHost($host);
    }
}
