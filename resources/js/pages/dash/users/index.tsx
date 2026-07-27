import { Head, router, usePage } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useTranslations } from '@/hooks/use-translations';
import { formatDate } from '@/lib/datetime';
import { index } from '@/routes/dash/users';
import UserForm, { type UserRow } from './form';

type Paginated = {
    data: UserRow[];
    total: number;
};

type Props = {
    users: Paginated;
    search: string;
    timezones: string[];
};

/**
 * Convention: the list is a list. Creating and editing live in a dialog
 * because the form is short. A long form would get its own page instead.
 * See AGENTS.md.
 */
export default function UsersIndex({ users, search, timezones }: Props) {
    const t = useTranslations();
    const { auth, locale } = usePage().props;
    const timezone = (auth.user.timezone as string) ?? 'UTC';

    const [term, setTerm] = useState(search);
    const [editing, setEditing] = useState<UserRow | null>(null);
    const [creating, setCreating] = useState(false);

    // Debounce: a request per keystroke would hammer the server and make
    // results arrive out of order.
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
            <Head title={t('users.title')} />

            <div className="density-panel space-y-4 p-4">
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <h1 className="text-base font-semibold">
                            {t('users.title')}
                        </h1>
                        <p className="text-muted-foreground">
                            {t('users.description')}
                        </p>
                    </div>

                    <div className="flex items-center gap-2">
                        <div className="relative">
                            <Search className="pointer-events-none absolute top-1/2 left-2 size-3.5 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={term}
                                onChange={(event) =>
                                    setTerm(event.target.value)
                                }
                                placeholder={t('users.search')}
                                className="w-56 pl-7"
                                data-test="users-search"
                            />
                        </div>

                        <Button
                            size="sm"
                            onClick={() => setCreating(true)}
                            data-test="users-add"
                        >
                            {t('users.add')}
                        </Button>
                    </div>
                </div>

                {users.data.length === 0 ? (
                    <div
                        className="rounded-md border py-12 text-center text-muted-foreground"
                        data-test="users-empty"
                    >
                        {search ? t('users.empty_search') : t('users.empty')}
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-md border">
                        <div className="flex bg-muted/40 px-3 py-1.5 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                            <span className="flex-[2]">{t('users.name')}</span>
                            <span className="flex-[2]">{t('users.email')}</span>
                            <span className="w-28">{t('users.locale')}</span>
                            <span className="w-32 text-right">
                                {t('users.created_at')}
                            </span>
                        </div>

                        {users.data.map((user) => (
                            <button
                                key={user.id}
                                type="button"
                                onClick={() => setEditing(user)}
                                className="flex w-full items-center border-t px-3 py-2 text-left hover:bg-muted/40"
                                data-test="users-row"
                            >
                                <span className="flex-[2] truncate">
                                    {user.name}
                                </span>
                                <span className="flex-[2] truncate text-muted-foreground">
                                    {user.email}
                                </span>
                                <span className="w-28">
                                    <Badge variant="outline">
                                        {user.locale}
                                    </Badge>
                                </span>
                                <span className="w-32 text-right text-muted-foreground">
                                    {formatDate(
                                        user.created_at,
                                        timezone,
                                        locale,
                                    )}
                                </span>
                            </button>
                        ))}
                    </div>
                )}
            </div>

            <UserForm
                user={editing}
                open={creating || editing !== null}
                timezones={timezones}
                onClose={() => {
                    setCreating(false);
                    setEditing(null);
                }}
            />
        </>
    );
}

UsersIndex.layout = {
    breadcrumbs: [{ title: 'users.title', href: index() }],
};
