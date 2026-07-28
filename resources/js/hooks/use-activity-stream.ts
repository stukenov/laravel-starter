import { useEffect, useRef, useState } from 'react';
import type { Entry } from '@/pages/dash/activity/index';

/**
 * RULE(sse): the browser reconnects, we do not keep the socket open.
 *
 * EventSource reconnects on its own and replays Last-Event-ID, so the
 * server can close a connection after ~25 seconds without losing anything.
 * That is what keeps a PHP-FPM worker from being held per viewer.
 *
 * See AGENTS.md.
 */
/**
 * How many live entries the page keeps.
 *
 * Neither the list nor the `seen` set was ever trimmed, and this page is
 * built to be left open — that is the entire point of the stream. A busy
 * project produces thousands of rows over a working day, and every one of
 * them stayed in memory and in the DOM.
 *
 * A hundred is more than fits on a screen and far more than anybody
 * scrolls back through in a live feed; the rest of the history is on the
 * page behind it, paginated, where it belongs.
 */
const LIVE_LIMIT = 100;

export function useActivityStream(
    url: string,
    enabled = true,
    known: string[] = [],
) {
    const [live, setLive] = useState<Entry[]>([]);
    const [connected, setConnected] = useState(false);

    // Seeded with what the page already rendered. The server starts a first
    // connection at the tip, but a reconnect resumes from a cursor that can
    // predate those rows — and then it would send them again.
    const seen = useRef(new Set(known));

    // What the page rendered, held in a ref rather than read from the
    // argument. The array is a new object on every render, so depending on
    // it would tear down the connection and open a new one each time —
    // which is the opposite of what a long-lived stream is for.
    const rendered = useRef(known);

    useEffect(() => {
        if (!enabled || typeof EventSource === 'undefined') {
            return;
        }

        const source = new EventSource(url, { withCredentials: true });

        source.addEventListener('open', () => setConnected(true));

        source.addEventListener('activity', (event) => {
            const entry = JSON.parse((event as MessageEvent).data) as Entry;

            // A reconnect can replay an entry the page already holds; the
            // guard is here rather than on the server because only the
            // client knows what it rendered.
            if (seen.current.has(entry.id)) {
                return;
            }

            seen.current.add(entry.id);

            setLive((current) => {
                const next = [entry, ...current].slice(0, LIVE_LIMIT);

                // The set exists to stop a reconnect replaying a row, so
                // it only has to remember what is still on screen plus
                // what the page rendered. Left to grow it outlives every
                // row it describes.
                seen.current = new Set([
                    ...rendered.current,
                    ...next.map((item) => item.id),
                ]);

                return next;
            });
        });

        // Not an error state: the server closes on purpose and the browser
        // reconnects. Showing "disconnected" here would blink every 25s.
        source.addEventListener('error', () => setConnected(false));

        return () => source.close();
    }, [url, enabled]);

    return { live, connected };
}
