<?php

declare(strict_types=1);

namespace App\Http\Controllers\Dash;

use App\Http\Controllers\Controller;
use App\Models\Activity;
use App\Support\Sse\PostgresListener;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * RULE(sse): the connection lives ~25 seconds on purpose — AGENTS.md.
 *
 * Every open SSE connection holds a PHP-FPM worker, so an unbounded stream
 * puts a hard ceiling on concurrent viewers. Instead the connection closes
 * after a short life and the browser's EventSource reconnects on its own,
 * sending Last-Event-ID. The log carries a bigint sequence, so the stream
 * resumes exactly where it broke and nothing is lost or repeated.
 *
 * Inside the connection the process sleeps on the socket and wakes on
 * NOTIFY — it never polls the table.
 */
class ActivityStreamController extends Controller
{
    /** A comment every few seconds keeps proxies from buffering the stream. */
    private const HEARTBEAT_SECONDS = 5;

    /**
     * How long the connection lives, and how long the browser waits
     * before opening the next one.
     *
     * The arithmetic matters and it is worth stating: a connection holds
     * an FPM worker for its whole life. With a 25-second life and the
     * browser's default reconnect of 2-3 seconds, one open tab occupies a
     * worker about 90% of the time — near enough to permanently.
     *
     * Which is why `retry` is sent explicitly rather than left to the
     * browser: it is the only lever the server has over the duty cycle.
     * Raise it and each viewer costs less; raise it too far and the feed
     * stops feeling live after a reconnect.
     *
     * Both are configurable because the right numbers depend on the pool
     * size, and the pool size depends on the project.
     */
    private function lifetime(): int
    {
        return Config::integer('sse.lifetime_seconds', 25);
    }

    private function retry(): int
    {
        return Config::integer('sse.retry_seconds', 3);
    }

    public function __invoke(Request $request): StreamedResponse
    {
        $this->authorize('viewAny', Activity::class);

        // A first connection carries no cursor and starts at the tip. Zero
        // would replay the whole log — every row the page already rendered
        // server-side, shown a second time. Only a reconnect catches up,
        // and it says from where.
        $cursor = $request->header('Last-Event-ID') ?? $request->query('since');

        $since = $cursor === null ? Activity::tip() : (int) $cursor;

        $response = new StreamedResponse(function () use ($since) {
            $listener = new PostgresListener('activity_log');
            $deadline = microtime(true) + $this->lifetime();

            try {
                // A byte right away. EventSource does not fire `open` until
                // something arrives, so without this the page sits five
                // seconds — until the first heartbeat — showing no live
                // badge on every connection, and it reconnects every 25.
                // The browser's own default is 2-3 seconds and varies.
                // Saying it out loud is the only control the server has
                // over how often a worker is taken.
                $this->write('retry: '.($this->retry() * 1000)."\n\n");
                $this->write(": open\n\n");

                // Anything written while the browser was reconnecting is
                // sent next, so a gap in the feed is impossible.
                $since = $this->flush(Activity::since($since), $since);

                while (microtime(true) < $deadline) {
                    // A viewer who closed the tab is gone, but the loop
                    // would keep its worker until the deadline — twenty
                    // more seconds of a worker held for nobody. PHP only
                    // learns the connection died by writing to it, which
                    // the heartbeat below does.
                    if (connection_aborted() !== 0) {
                        break;
                    }

                    $payload = $listener->wait(self::HEARTBEAT_SECONDS);

                    if ($payload === null) {
                        // No news. The comment is not data — EventSource
                        // ignores it, proxies see traffic.
                        $this->write(": heartbeat\n\n");

                        continue;
                    }

                    $since = $this->flush(Activity::since($since), $since);
                }
            } finally {
                $listener->close();
            }
        });

        $response->headers->add([
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache, no-transform',
            // Nginx buffers by default, which would hold every event until
            // the connection closes — defeating the point entirely.
            'X-Accel-Buffering' => 'no',
        ]);

        return $response;
    }

    /**
     * @param  Collection<int, Activity>  $entries
     */
    private function flush($entries, int $since): int
    {
        foreach ($entries as $entry) {
            $this->write(sprintf(
                "id: %d\nevent: activity\ndata: %s\n\n",
                $entry->seq,
                json_encode($entry->toFeedItem(), JSON_UNESCAPED_UNICODE),
            ));

            $since = max($since, (int) $entry->seq);
        }

        return $since;
    }

    private function write(string $chunk): void
    {
        echo $chunk;

        // Both buffers, or the bytes sit in PHP and never reach the socket.
        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }
}
