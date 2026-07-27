import { Form, Head, usePage } from '@inertiajs/react';
import { Monitor, Smartphone } from 'lucide-react';
import SessionController from '@/actions/App/Http/Controllers/Settings/SessionController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useTranslations } from '@/hooks/use-translations';
import { formatDateTime } from '@/lib/datetime';
import { index } from '@/routes/sessions';

type Session = {
    id: string;
    browser: string | null;
    platform: string | null;
    device: string | null;
    ip: string | null;
    last_active_at: string;
    is_current: boolean;
};

type Props = {
    sessions: Session[];
};

export default function Sessions({ sessions }: Props) {
    const t = useTranslations();
    const { auth, locale } = usePage().props;
    const timezone = (auth.user.timezone as string) ?? 'UTC';

    const others = sessions.filter((session) => !session.is_current);

    return (
        <>
            <Head title={t('sessions.title')} />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title={t('sessions.title')}
                    description={t('sessions.description')}
                />

                <div className="overflow-hidden rounded-md border">
                    {sessions.map((session) => {
                        const isPhone = session.device === 'smartphone';

                        return (
                            <div
                                key={session.id}
                                className="flex items-center gap-3 border-b px-3 py-2.5 last:border-b-0"
                                data-test="session-row"
                            >
                                {isPhone ? (
                                    <Smartphone className="size-4 shrink-0 text-muted-foreground" />
                                ) : (
                                    <Monitor className="size-4 shrink-0 text-muted-foreground" />
                                )}

                                <div className="min-w-0 flex-1">
                                    <p className="flex items-center gap-2">
                                        <span className="truncate">
                                            {session.browser ??
                                                t('sessions.unknown_browser')}
                                            {session.platform
                                                ? ` — ${session.platform}`
                                                : ''}
                                        </span>
                                        {session.is_current && (
                                            <Badge data-test="current-session">
                                                {t('sessions.current')}
                                            </Badge>
                                        )}
                                    </p>
                                    <p className="text-xs text-muted-foreground">
                                        {session.ip ?? t('sessions.unknown_ip')}
                                        {' · '}
                                        {t('sessions.last_active')}:{' '}
                                        {formatDateTime(
                                            session.last_active_at,
                                            timezone,
                                            locale,
                                        )}
                                    </p>
                                </div>
                            </div>
                        );
                    })}
                </div>

                {others.length === 0 ? (
                    <p className="text-muted-foreground">
                        {t('sessions.only_current')}
                    </p>
                ) : (
                    <Dialog>
                        <DialogTrigger asChild>
                            <Button
                                variant="destructive"
                                size="sm"
                                data-test="logout-others"
                            >
                                {t('sessions.logout_others')}
                            </Button>
                        </DialogTrigger>

                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    {t('sessions.logout_others_confirm')}
                                </DialogTitle>
                                <DialogDescription>
                                    {t('sessions.logout_others_explain')}
                                </DialogDescription>
                            </DialogHeader>

                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button variant="secondary" size="sm">
                                        {t('sessions.cancel')}
                                    </Button>
                                </DialogClose>

                                <Form {...SessionController.destroy.form()}>
                                    {({ processing }) => (
                                        <Button
                                            variant="destructive"
                                            size="sm"
                                            disabled={processing}
                                            data-test="logout-others-confirm"
                                        >
                                            {t('sessions.logout_others')}
                                        </Button>
                                    )}
                                </Form>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                )}
            </div>
        </>
    );
}

Sessions.layout = {
    breadcrumbs: [{ title: 'sessions.title', href: index() }],
};
