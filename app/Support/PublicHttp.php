<?php

namespace App\Support;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;
use RuntimeException;

/**
 * Reads a public https address someone gave the app (a calendar link, a
 * recipe page). Never an address in the server's own network, not even
 * after a redirect, and never more than a limited size.
 */
class PublicHttp
{
    /**
     * @throws RuntimeException When the address is not allowed, does not answer or is too large.
     */
    public static function get(string $url, int $maxBytes, int $timeout = 20): string
    {
        if (! PublicAddress::isPublicHttpsUrl($url)) {
            throw new RuntimeException('Adressen går inte att hämta.');
        }

        try {
            $response = Http::timeout($timeout)
                ->connectTimeout(5)
                ->retry(2, 1000, throw: false)
                ->withHeaders(['User-Agent' => 'NPF-familjen (+'.config('app.url').')'])
                ->withOptions([
                    'allow_redirects' => [
                        'max' => 3,
                        'protocols' => ['https'],
                        'on_redirect' => function (RequestInterface $request, $response, UriInterface $uri): void {
                            if (! PublicAddress::isPublicHost($uri->getHost())) {
                                throw new RuntimeException('Adressen skickades vidare till en adress som inte går att hämta.');
                            }
                        },
                    ],
                ])
                ->get($url)
                ->throw();
        } catch (ConnectionException|RequestException $exception) {
            throw new RuntimeException('Adressen svarade inte.', previous: $exception);
        }

        $body = $response->body();

        if (strlen($body) > $maxBytes) {
            throw new RuntimeException('Sidan är för stor.');
        }

        return $body;
    }
}
