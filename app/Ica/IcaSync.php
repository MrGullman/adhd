<?php

namespace App\Ica;

use App\Models\IcaConnection;
use Illuminate\Support\Str;

/**
 * Keeps a family's ICA list in step with their shopping list, one way: what
 * is added, ticked off or removed here happens in ICA too. Always from the
 * queue (SyncIcaListItem), never while a page is shown. A failure is kept on
 * the connection and shown calmly; the family's own list is never affected.
 */
class IcaSync
{
    public function __construct(private IcaClient $client) {}

    /**
     * A fresh access token, renewed with the refresh token a day before it runs out.
     */
    public function accessToken(IcaConnection $connection, bool $renew = false): string
    {
        if ($renew || $connection->token_expires_at === null || $connection->token_expires_at->lt(now()->addDay())) {
            $token = $this->client->refresh($connection->client, $connection->token);
            $connection->forceFill([
                'token' => $token,
                'token_expires_at' => now()->addSeconds((int) ($token['expires_in'] ?? 2_592_000)),
            ])->save();
        }

        return (string) ($connection->token['access_token'] ?? '');
    }

    /**
     * Runs one change against ICA and notes how it went on the connection.
     *
     * @template T
     *
     * @param  callable(string $accessToken, string $listId): T  $change
     * @return T|null
     */
    public function run(IcaConnection $connection, callable $change): mixed
    {
        if ($connection->list_id === null) {
            return null;
        }

        try {
            try {
                $result = $change($this->accessToken($connection), $connection->list_id);
            } catch (IcaException $exception) {
                // Logged out at ICA before the token ran out: renew once and try again.
                if ($exception->getCode() !== 401) {
                    throw $exception;
                }

                $result = $change($this->accessToken($connection, renew: true), $connection->list_id);
            }

            $connection->forceFill(['last_synced_at' => now(), 'last_error' => null])->save();

            return $result;
        } catch (IcaException $exception) {
            $connection->forceFill(['last_error' => Str::limit($exception->getMessage(), 250, '')])->save();

            throw $exception;
        }
    }
}
