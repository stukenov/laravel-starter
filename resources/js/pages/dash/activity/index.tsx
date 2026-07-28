import { Head, router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslations } from '@/hooks/use-translations';
import { formatDateTime } from '@/lib/datetime';
import { useActivityStream } from '@/hooks/use-activity-stream';
import { index, stream } from '@/routes/dash/activity';
import { usePage } from '@inertiajs/react';

export type Change = { field: string; from: unknown; to: unknown };

export type Entry = {
    id: string;
    log_name: string | null;
    event: string | null;
    description: string;
    subject_type: string | null;
    subject_id: string | null;
    causer: { id: string; name: string } | null;
    changes: Change[];
    created_at: string | null;
};

type Props = {
    entries: { data: Entry[] };
    filters: { log: string; event: string };
    logs: string[];
    events: string[];
};

const ALL = '__all__';

/**
 * Read only: an audit log that can be edited is not an audit log.
 */
export default function ActivityIndex({
    entries,
    filters,
    logs,
    events,
}: Props) {
    const t = useTranslations();
    const { auth, locale } = usePage().props;
    const timezone = (auth.user.timezone as string) ?? 'UTC';

    // The live feed is disabled while a filter is on: the stream pushes
    // everything, and silently mixing unfiltered entries into a filtered
    // list would be a lie about what the screen is showing.
    const filtering = filters.log !== '' || filters.event !== '';
    const { live, connected } = useActivityStream(
        stream().url,
        !filtering,
        entries.data.map((entry) => entry.id),
    );

    const rows = [...live, ...entries.data];

    const apply = (next: Partial<typeof filters>) => {
        const merged = { ...filters, ...next };

        router.get(
            index().url,
            Object.fromEntries(
                Object.entries(merged).filter(([, value]) => value !== ''),
            ),
            { preserveState: true, replace: true },
        );
    };

    const label = (value: unknown) =>
        value === null || value === '' ? '—' : String(value);

    return (
        <>
            <Head title={t('activity.title')} />

            <div className="density-panel space-y-4 p-4">
                {/* RULE(mobile): a row that never wraps is a desktop idea —
                    below sm the filters wrap onto their own line and share
                    the row instead of being cut off at a fixed width.
                    See AGENTS.md. */}
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <h1 className="text-base font-semibold">
                            {t('activity.title')}
                        </h1>
                        <p className="flex items-center gap-2 text-muted-foreground">
                            {t('activity.description')}
                            {connected && (
                                <span
                                    className="flex items-center gap-1 text-xs text-primary"
                                    data-test="activity-live"
                                >
                                    <span className="size-1.5 rounded-full bg-primary" />
                                    {t('activity.live')}
                                </span>
                            )}
                        </p>
                    </div>

                    <div className="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                        <Select
                            value={filters.log || ALL}
                            onValueChange={(value) =>
                                apply({ log: value === ALL ? '' : value })
                            }
                        >
                            <SelectTrigger
                                className="min-w-0 flex-1 sm:w-40 sm:flex-none"
                                data-test="filter-log"
                            >
                                <SelectValue
                                    placeholder={t('activity.all_logs')}
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ALL}>
                                    {t('activity.all_logs')}
                                </SelectItem>
                                {logs.map((name) => (
                                    <SelectItem key={name} value={name}>
                                        {name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Select
                            value={filters.event || ALL}
                            onValueChange={(value) =>
                                apply({ event: value === ALL ? '' : value })
                            }
                        >
                            <SelectTrigger
                                className="min-w-0 flex-1 sm:w-40 sm:flex-none"
                                data-test="filter-event"
                            >
                                <SelectValue
                                    placeholder={t('activity.all_events')}
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value={ALL}>
                                    {t('activity.all_events')}
                                </SelectItem>
                                {events.map((name) => (
                                    <SelectItem key={name} value={name}>
                                        {t(`activity.events.${name}`)}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                {rows.length === 0 ? (
                    <div
                        className="rounded-md border py-12 text-center text-muted-foreground"
                        data-test="activity-empty"
                    >
                        {filters.log || filters.event
                            ? t('activity.empty_filter')
                            : t('activity.empty')}
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-md border">
                        {rows.map((entry) => (
                            <div
                                key={entry.id}
                                className="border-b px-3 py-2 last:border-b-0"
                                data-test="activity-row"
                            >
                                <div className="flex items-baseline gap-2">
                                    <span className="font-medium">
                                        {entry.causer?.name ??
                                            t('activity.system')}
                                    </span>
                                    <span className="text-muted-foreground">
                                        {entry.event
                                            ? t(
                                                  `activity.events.${entry.event}`,
                                              )
                                            : entry.description}
                                    </span>
                                    {entry.subject_type && (
                                        <Badge variant="outline">
                                            {entry.subject_type}
                                        </Badge>
                                    )}
                                    <span className="ml-auto text-xs text-muted-foreground">
                                        {formatDateTime(
                                            entry.created_at,
                                            timezone,
                                            locale,
                                        )}
                                    </span>
                                </div>

                                {entry.changes.length > 0 ? (
                                    <ul className="mt-1 space-y-0.5">
                                        {entry.changes.map((change) => (
                                            <li
                                                key={change.field}
                                                className="text-xs text-muted-foreground"
                                                data-test="activity-change"
                                            >
                                                <code>{change.field}</code>:{' '}
                                                {label(change.from)} →{' '}
                                                <span className="text-foreground">
                                                    {label(change.to)}
                                                </span>
                                            </li>
                                        ))}
                                    </ul>
                                ) : (
                                    <p className="mt-1 text-xs text-muted-foreground">
                                        {t('activity.no_changes')}
                                    </p>
                                )}
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

ActivityIndex.layout = {
    breadcrumbs: [{ title: 'activity.title', href: index() }],
};
