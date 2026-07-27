import { act, renderHook } from '@testing-library/react';
import { afterEach, beforeEach, describe, expect, it } from 'vitest';
import { useActivityStream } from '@/hooks/use-activity-stream';

/**
 * A stand-in for EventSource. jsdom has none, and a real one would need a
 * server — the hook's whole job is what it does with the events, so the
 * transport is the part worth faking.
 */
class FakeEventSource {
    static instances: FakeEventSource[] = [];

    listeners: Record<string, ((event: unknown) => void)[]> = {};

    closed = false;

    constructor(public url: string) {
        FakeEventSource.instances.push(this);
    }

    addEventListener(type: string, handler: (event: unknown) => void) {
        (this.listeners[type] ??= []).push(handler);
    }

    close() {
        this.closed = true;
    }

    emit(type: string, data?: string) {
        act(() => {
            (this.listeners[type] ?? []).forEach((handler) =>
                handler({ data }),
            );
        });
    }

    static get last() {
        return FakeEventSource.instances[FakeEventSource.instances.length - 1];
    }
}

function entry(overrides: Record<string, unknown> = {}) {
    return JSON.stringify({
        id: '019fa2f8-0000-7000-8000-000000000001',
        log_name: 'user',
        event: 'updated',
        description: 'обновление',
        subject_type: 'App\\Models\\User',
        subject_id: 'u1',
        causer: { id: 'c1', name: 'Сакен' },
        changes: [],
        created_at: '2026-07-27T12:00:00Z',
        ...overrides,
    });
}

beforeEach(() => {
    FakeEventSource.instances = [];
    // @ts-expect-error — replacing a global the test environment lacks
    globalThis.EventSource = FakeEventSource;
});

afterEach(() => {
    // @ts-expect-error — same global
    delete globalThis.EventSource;
});

describe('activity stream', () => {
    // Correct data
    it('collects an entry that arrives', () => {
        const { result } = renderHook(() =>
            useActivityStream('/dash/activity/stream'),
        );

        FakeEventSource.last.emit('activity', entry());

        expect(result.current.live).toHaveLength(1);
        expect(result.current.live[0].causer?.name).toBe('Сакен');
    });

    it('puts the newest entry first', () => {
        const { result } = renderHook(() =>
            useActivityStream('/dash/activity/stream'),
        );

        FakeEventSource.last.emit('activity', entry({ id: 'a' }));
        FakeEventSource.last.emit('activity', entry({ id: 'b' }));

        expect(result.current.live.map((item) => item.id)).toEqual(['b', 'a']);
    });

    it('reports the connection once it opens', () => {
        const { result } = renderHook(() =>
            useActivityStream('/dash/activity/stream'),
        );

        expect(result.current.connected).toBe(false);

        FakeEventSource.last.emit('open');

        expect(result.current.connected).toBe(true);
    });

    it('closes the connection when the page goes away', () => {
        const { unmount } = renderHook(() =>
            useActivityStream('/dash/activity/stream'),
        );

        const source = FakeEventSource.last;
        unmount();

        expect(source.closed).toBe(true);
    });

    // Wrong data
    it('ignores an entry it has already shown', () => {
        // A reconnect replays from Last-Event-ID and can repeat the entry
        // that was in flight when the connection dropped.
        const { result } = renderHook(() =>
            useActivityStream('/dash/activity/stream'),
        );

        FakeEventSource.last.emit('activity', entry());
        FakeEventSource.last.emit('activity', entry());

        expect(result.current.live).toHaveLength(1);
    });

    it('does not treat a closed connection as a failure', () => {
        // The server closes on purpose every ~25 seconds. Anything louder
        // than dropping the badge would blink at the user constantly.
        const { result } = renderHook(() =>
            useActivityStream('/dash/activity/stream'),
        );

        FakeEventSource.last.emit('open');
        FakeEventSource.last.emit('error');

        expect(result.current.connected).toBe(false);
        expect(result.current.live).toHaveLength(0);
    });

    it('ignores an entry the page had already rendered', () => {
        // A reconnect resumes from a cursor that can predate the rows the
        // server rendered into the page, and would then repeat them.
        const { result } = renderHook(() =>
            useActivityStream('/dash/activity/stream', true, ['already-here']),
        );

        FakeEventSource.last.emit('activity', entry({ id: 'already-here' }));

        expect(result.current.live).toHaveLength(0);
    });

    it('opens nothing when the stream is disabled', () => {
        // The page turns it off while a filter is on: the stream pushes
        // everything, and mixing that into a filtered list would misreport
        // what the screen is showing.
        renderHook(() => useActivityStream('/dash/activity/stream', false));

        expect(FakeEventSource.instances).toHaveLength(0);
    });

    // Empty data
    it('starts with nothing', () => {
        const { result } = renderHook(() =>
            useActivityStream('/dash/activity/stream'),
        );

        expect(result.current.live).toEqual([]);
        expect(result.current.connected).toBe(false);
    });

    it('survives an entry with no causer and no changes', () => {
        const { result } = renderHook(() =>
            useActivityStream('/dash/activity/stream'),
        );

        FakeEventSource.last.emit(
            'activity',
            entry({ causer: null, changes: [], event: null }),
        );

        expect(result.current.live[0].causer).toBeNull();
        expect(result.current.live[0].changes).toEqual([]);
    });
});
