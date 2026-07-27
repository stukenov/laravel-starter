<?php

namespace App\Support\Sse;

use PgSql\Connection;
use RuntimeException;

/**
 * RULE(sse): sleep on the socket, never poll the table — AGENTS.md.
 *
 * A dedicated libpq connection, because LISTEN occupies it for the whole
 * wait and the pooled PDO connection is needed for ordinary queries.
 *
 * The wait itself is stream_select() over pg_socket(): the process sleeps
 * until Postgres pushes a notification and burns no CPU meanwhile. Polling
 * the table every second would work too and would be wrong — it costs a
 * query per client per second forever.
 */
class PostgresListener
{
    private Connection $connection;

    private bool $closed = false;

    public function __construct(private readonly string $channel)
    {
        $config = config('database.connections.pgsql');

        $dsn = sprintf(
            'host=%s port=%s dbname=%s user=%s password=%s',
            $config['host'], $config['port'], $config['database'],
            $config['username'], $config['password'],
        );

        // FORCE_NEW is not optional. pg_connect() hands back an existing
        // connection whenever the connection string matches, so any other
        // caller with the same credentials would be given this one — and
        // its pg_close() would tear down a LISTEN that is mid-wait.
        $connection = pg_connect($dsn, PGSQL_CONNECT_FORCE_NEW);

        if ($connection === false) {
            throw new RuntimeException('Could not open a listening connection to PostgreSQL.');
        }

        $this->connection = $connection;

        pg_query($this->connection, 'listen "'.$channel.'"');
    }

    /**
     * Waits up to $seconds for a notification. Returns its payload, or null
     * if the wait ran out — the caller uses that to send a heartbeat.
     */
    public function wait(float $seconds): ?string
    {
        $socket = pg_socket($this->connection);

        if ($socket === false) {
            throw new RuntimeException('Could not obtain the PostgreSQL socket.');
        }

        $read = [$socket];
        $write = $except = [];

        $ready = stream_select($read, $write, $except, (int) $seconds, (int) (fmod($seconds, 1) * 1_000_000));

        if ($ready === false || $ready === 0) {
            return null;
        }

        pg_consume_input($this->connection);

        $notification = pg_get_notify($this->connection, PGSQL_ASSOC);

        return $notification === false ? null : (string) $notification['payload'];
    }

    public function close(): void
    {
        if ($this->closed) {
            return;
        }

        pg_query($this->connection, 'unlisten "'.$this->channel.'"');
        pg_close($this->connection);

        $this->closed = true;
    }
}
