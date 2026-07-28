import { Head, usePage } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { useTranslations } from '@/hooks/use-translations';
import { formatDateTime } from '@/lib/datetime';
import { index } from '@/routes/dash/health';

type Check = {
    name: string;
    label: string;
    status: string;
    summary: string | null;
    message: string | null;
};

type Props = {
    checks: Check[];
    finished_at: string | null;
    healthy: boolean;
};

/**
 * RULE(external): no third-party domain at render time — AGENTS.md.
 *
 * Spatie's own dashboard opens with a stylesheet from rsms.me, which the
 * browser blocks here. Same data, our own page, one design language.
 */
export default function HealthIndex({ checks, finished_at, healthy }: Props) {
    const t = useTranslations();
    const { auth, locale } = usePage().props;
    const timezone = (auth.user.timezone as string) ?? 'UTC';

    const tone = (status: string) =>
        status === 'ok'
            ? 'text-muted-foreground'
            : status === 'warning'
              ? 'text-amber-600'
              : 'text-destructive';

    return (
        <>
            <Head title={t('health.title')} />

            <div className="density-panel space-y-4 p-4">
                <div className="flex items-start justify-between gap-3">
                    <div>
                        <h1 className="text-base font-semibold">
                            {t('health.title')}
                        </h1>
                        <p className="text-muted-foreground">
                            {t('health.description')}
                        </p>
                    </div>

                    <Badge
                        variant={healthy ? 'secondary' : 'destructive'}
                        data-test="health-status"
                    >
                        {healthy ? t('health.healthy') : t('health.unhealthy')}
                    </Badge>
                </div>

                {checks.length === 0 ? (
                    <div
                        className="rounded-lg border border-border p-8 text-center text-muted-foreground"
                        data-test="health-empty"
                    >
                        {/* Not "everything is fine": it means the schedule
                            has never run, which is the thing worth saying. */}
                        {t('health.never_ran')}
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-lg border border-border">
                        {checks.map((check) => (
                            <div
                                key={check.name}
                                className="flex items-baseline justify-between gap-4 border-b border-border px-3 py-2 last:border-b-0"
                                data-test="health-row"
                            >
                                <span className="font-medium">
                                    {check.label}
                                </span>
                                <span
                                    className={tone(check.status)}
                                    data-test="health-summary"
                                >
                                    {check.summary ??
                                        check.message ??
                                        t(`health.status.${check.status}`)}
                                </span>
                            </div>
                        ))}
                    </div>
                )}

                {finished_at && (
                    <p
                        className="text-muted-foreground"
                        data-test="health-finished-at"
                    >
                        {t('health.checked_at')}{' '}
                        {formatDateTime(
                            finished_at,
                            timezone,
                            locale as string,
                        )}
                    </p>
                )}
            </div>
        </>
    );
}

// A translation key, not text: a static object cannot call a hook, so the
// layout resolves it. See AGENTS.md.
HealthIndex.layout = {
    breadcrumbs: [{ title: 'health.title', href: index() }],
};
