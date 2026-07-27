import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { useTranslations } from '@/hooks/use-translations';

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

/**
 * RULE(mobile): a touch target is 44px — AGENTS.md.
 *
 * Two links and a count, not a row of numbered pages: numbers are a
 * desktop habit and at 390px they wrap into a mess. Someone who needs a
 * particular record searches for it.
 *
 * This exists because the panel shipped without it. Every list called
 * `paginate(20)`, the server sent `links` and `next_page_url` in the
 * props, and no page rendered them — so a list of 37 users showed 20 and
 * offered no way at all to reach the rest. Nothing errored and no test
 * looked.
 */
export function Pagination<T>({ page }: { page: Paginated<T> }) {
    const t = useTranslations();

    if (page.last_page <= 1) {
        return null;
    }

    return (
        <nav
            className="flex flex-col items-center justify-between gap-3 sm:flex-row"
            data-test="pagination"
        >
            <span className="text-muted-foreground" data-test="pagination-count">
                {t('pagination.showing', {
                    page: page.current_page,
                    pages: page.last_page,
                    total: page.total,
                })}
            </span>

            <div className="flex gap-2">
                <Button
                    asChild={page.prev_page_url !== null}
                    variant="secondary"
                    size="sm"
                    className="min-h-11 sm:min-h-0"
                    disabled={page.prev_page_url === null}
                    data-test="pagination-prev"
                >
                    {page.prev_page_url ? (
                        <Link href={page.prev_page_url} preserveScroll>
                            {t('pagination.previous')}
                        </Link>
                    ) : (
                        <span>{t('pagination.previous')}</span>
                    )}
                </Button>

                <Button
                    asChild={page.next_page_url !== null}
                    variant="secondary"
                    size="sm"
                    className="min-h-11 sm:min-h-0"
                    disabled={page.next_page_url === null}
                    data-test="pagination-next"
                >
                    {page.next_page_url ? (
                        <Link href={page.next_page_url} preserveScroll>
                            {t('pagination.next')}
                        </Link>
                    ) : (
                        <span>{t('pagination.next')}</span>
                    )}
                </Button>
            </div>
        </nav>
    );
}
