import { Head, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import type { Paginated } from '@/components/pagination';
import { Pagination } from '@/components/pagination';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/hooks/use-translations';
import type { DocRow } from './form';
import RecordForm from './form';
import { index } from '@/routes/dash/docs';

type Props = {
    records: Paginated<DocRow>;
    search: string;
};

/**
 * Convention: a list is a list. Creating and editing live in a dialog
 * because the form is short. See AGENTS.md.
 */
export default function DocsIndex({ records, search }: Props) {
    const t = useTranslations();
    // RULE(time): the server sends ISO 8601, the client formats it in
    // the reader's zone — AGENTS.md.

    const [term, setTerm] = useState(search);
    const [editing, setEditing] = useState<DocRow | null>(null);
    const [creating, setCreating] = useState(false);

    // Debounce: a request per keystroke hammers the server and lets
    // responses arrive out of order.
    useEffect(() => {
        if (term === search) {
            return;
        }

        const timer = setTimeout(() => {
            router.get(index().url, term ? { search: term } : {}, {
                preserveState: true,
                replace: true,
            });
        }, 300);

        return () => clearTimeout(timer);
    }, [term, search]);

    return (
        <>
            <Head title={t('docs.title')} />

            <div className="density-panel space-y-4 p-4">
                {/* RULE(mobile): the base class is the phone. This row
                    stacks, and only turns into a row on a wide screen —
                    written the other way round the title and the search
                    box fight over 390px and the search box wins. */}
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h1 className="text-base font-semibold">
                            {t('docs.title')}
                        </h1>
                        <p className="text-muted-foreground">
                            {t('docs.description')}
                        </p>
                    </div>

                    <div className="flex w-full items-center gap-2 sm:w-auto">
                        <div className="relative flex-1 sm:flex-none">
                            <Search className="pointer-events-none absolute top-1/2 left-2 size-3.5 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={term}
                                onChange={(event) =>
                                    setTerm(event.target.value)
                                }
                                placeholder={t('docs.search')}
                                className="w-full pl-7 sm:w-56"
                                data-test="docs-search"
                            />
                        </div>

                        <Button
                            size="sm"
                            onClick={() => setCreating(true)}
                            data-test="docs-add"
                        >
                            {t('docs.add')}
                        </Button>
                    </div>
                </div>

                {records.data.length === 0 ? (
                    <div
                        className="rounded-md border py-12 text-center text-muted-foreground"
                        data-test="docs-empty"
                    >
                        {search ? t('docs.empty_search') : t('docs.empty')}
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-md border">
                        {/* RULE(mobile): a row of fixed columns is a
                            desktop idea. Below sm the row becomes a card
                            and the header has nothing left to label, so
                            it steps out of the way. */}
                        <div className="hidden bg-muted/40 px-3 py-1.5 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase sm:flex">
                            <span className="flex-1">
                                {t('docs.fields.title')}
                            </span>
                            <span className="flex-1">
                                {t('docs.fields.attachment')}
                            </span>
                        </div>

                        {records.data.map((record) => (
                            <button
                                key={record.id}
                                type="button"
                                onClick={() => setEditing(record)}
                                className="flex min-h-11 w-full flex-col gap-1 border-t px-3 py-2 text-left hover:bg-muted/40 sm:flex-row sm:items-center sm:gap-3"
                                data-test="docs-row"
                            >
                                <span className="truncate font-medium sm:flex-1 sm:font-normal">
                                    {String(record.title ?? '')}
                                </span>
                                <span className="truncate text-muted-foreground sm:flex-1">
                                    {String(record.attachment ?? '')}
                                </span>
                            </button>
                        ))}
                    </div>
                )}

                <Pagination page={records} />
            </div>

            <RecordForm
                record={editing}
                open={creating || editing !== null}

                onClose={() => {
                    setCreating(false);
                    setEditing(null);
                }}
            />
        </>
    );
}

DocsIndex.layout = {
    breadcrumbs: [{ title: 'docs.title', href: index() }],
};
