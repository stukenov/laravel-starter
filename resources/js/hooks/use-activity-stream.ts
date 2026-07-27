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
export function useActivityStream(url: string, enabled = true, known: string[] = []) {
    const [live, setLive] = useState<Entry[]>([]);
    const [connected, setConnected] = useState(false);

    // Seeded with what the page already rendered. The server starts a first
    // connection at the tip, but a reconnect resumes from a cursor that can
    // predate those rows — and then it would send them again.
    const seen = useRef(new Set(known));

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
            setLive((current) => [entry, ...current]);
        });

        // Not an error state: the server closes on purpose and the browser
        // reconnects. Showing "disconnected" here would blink every 25s.
        source.addEventListener('error', () => setConnected(false));

        return () => source.close();
    }, [url, enabled]);

    return { live, connected };
}
