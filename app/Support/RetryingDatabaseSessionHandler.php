<?php

namespace App\Support;

use Illuminate\Database\DetectsConcurrencyErrors;
use Illuminate\Database\QueryException;
use Illuminate\Session\DatabaseSessionHandler;

/**
 * Sessions in the database, saved again when two requests with the same
 * session collide (MariaDB deadlock), e.g. a page that asks every few
 * seconds while another tab is open. Without this, one of them fails.
 */
class RetryingDatabaseSessionHandler extends DatabaseSessionHandler
{
    use DetectsConcurrencyErrors;

    private const ATTEMPTS = 3;

    public function write($sessionId, $data): bool
    {
        for ($attempt = 1; ; $attempt++) {
            try {
                return parent::write($sessionId, $data);
            } catch (QueryException $exception) {
                if ($attempt >= self::ATTEMPTS || ! $this->causedByConcurrencyError($exception)) {
                    throw $exception;
                }

                usleep(50_000 * $attempt);
            }
        }
    }
}
