<?php

namespace Tests\Feature;

use App\Support\RetryingDatabaseSessionHandler;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PDOException;
use Tests\TestCase;

/**
 * Two requests with the same session saving at once: the session is saved
 * again instead of the request failing.
 */
class SessionDeadlockTest extends TestCase
{
    use RefreshDatabase;

    private function handler(int $deadlocks): RetryingDatabaseSessionHandler
    {
        return new class(DB::connection(), 'sessions', 120, app(), $deadlocks) extends RetryingDatabaseSessionHandler
        {
            public int $tries = 0;

            public function __construct($connection, $table, $minutes, $container, private int $deadlocks)
            {
                parent::__construct($connection, $table, $minutes, $container);
            }

            protected function performInsert($sessionId, $payload)
            {
                if (++$this->tries <= $this->deadlocks) {
                    throw new QueryException('mariadb', 'insert into sessions', [], new PDOException('SQLSTATE[40001]: Serialization failure: 1213 Deadlock found when trying to get lock; try restarting transaction', 40001));
                }

                return parent::performInsert($sessionId, $payload);
            }
        };
    }

    public function test_a_session_is_saved_again_after_a_deadlock()
    {
        $handler = $this->handler(deadlocks: 2);

        $this->assertTrue($handler->write('session-a', serialize(['_token' => 'x'])));
        $this->assertSame(3, $handler->tries);
        $this->assertDatabaseHas('sessions', ['id' => 'session-a']);
    }

    public function test_it_gives_up_after_a_few_tries_and_other_errors_are_not_hidden()
    {
        $this->expectException(QueryException::class);

        $this->handler(deadlocks: 5)->write('session-b', serialize([]));
    }
}
