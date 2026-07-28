import { Form, Head, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import TokenController from '@/actions/App/Http/Controllers/Settings/TokenController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/hooks/use-translations';
import { formatDateTime } from '@/lib/datetime';
import { destroy, index } from '@/routes/tokens';

type Token = {
    id: string;
    name: string;
    created_at: string | null;
    last_used_at: string | null;
};

type Props = {
    tokens: Token[];
    /**
     * The plaintext, for exactly one render.
     *
     * A prop rather than a flash event: `Inertia::flash` fires something
     * a toast consumes and forgets, which is right for "saved" and wrong
     * for a value the person has to read off the screen and copy before
     * it is gone for good. Shown once because only the hash is stored,
     * and the banner says so.
     */
    created?: string | null;
};

export default function Tokens({ tokens, created }: Props) {
    const t = useTranslations();
    const { auth, locale } = usePage().props;
    const timezone = (auth.user.timezone as string) ?? 'UTC';

    const [copied, setCopied] = useState(false);
    const [confirming, setConfirming] = useState<string | null>(null);

    return (
        <>
            <Head title={t('tokens.title')} />

            <div className="flex flex-col gap-6">
                <Heading
                    title={t('tokens.title')}
                    description={t('tokens.description')}
                />

                {created && (
                    <div
                        className="flex flex-col gap-2 border border-primary/40 bg-primary/5 p-3"
                        data-test="token-plaintext"
                    >
                        <p className="text-sm font-medium">
                            {t('tokens.copy_once')}
                        </p>
                        <div className="flex flex-col gap-2 sm:flex-row sm:items-center">
                            <code className="min-w-0 flex-1 overflow-x-auto rounded-md bg-muted px-2 py-1.5 font-mono text-xs">
                                {created}
                            </code>
                            <Button
                                type="button"
                                size="sm"
                                variant="secondary"
                                className="min-h-11 sm:min-h-8"
                                onClick={() => {
                                    void navigator.clipboard.writeText(created);
                                    setCopied(true);
                                }}
                            >
                                {copied ? t('tokens.copied') : t('tokens.copy')}
                            </Button>
                        </div>
                    </div>
                )}

                <Form
                    {...TokenController.store.form()}
                    options={{ preserveScroll: true }}
                    resetOnSuccess
                    className="flex flex-col gap-2"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-1.5">
                                <Label htmlFor="name">{t('tokens.name')}</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    required
                                    maxLength={60}
                                    placeholder={t('tokens.name_hint')}
                                    data-test="token-name"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <Button
                                size="sm"
                                disabled={processing}
                                className="min-h-11 self-start sm:min-h-8"
                                data-test="token-create"
                            >
                                {processing && <Spinner />}
                                {t('tokens.create')}
                            </Button>
                        </>
                    )}
                </Form>

                {tokens.length === 0 ? (
                    <p
                        className="text-muted-foreground"
                        data-test="tokens-empty"
                    >
                        {t('tokens.empty')}
                    </p>
                ) : (
                    <div className="overflow-hidden rounded-md border">
                        {tokens.map((token) => (
                            <div
                                key={token.id}
                                className="flex min-h-11 flex-col gap-1 border-t px-3 py-2 first:border-t-0 sm:flex-row sm:items-center sm:gap-3"
                                data-test="token-row"
                            >
                                <span className="truncate font-medium sm:flex-1 sm:font-normal">
                                    {token.name}
                                </span>
                                <span className="text-xs text-muted-foreground sm:w-48">
                                    {t('tokens.last_used')}:{' '}
                                    {token.last_used_at
                                        ? formatDateTime(
                                              token.last_used_at,
                                              timezone,
                                              locale,
                                          )
                                        : t('tokens.never_used')}
                                </span>
                                {/* Two clicks: revoking is irreversible and
                                    breaks whatever is using the token. */}
                                <Button
                                    type="button"
                                    variant="destructive"
                                    size="sm"
                                    className="min-h-11 self-start sm:min-h-8 sm:self-auto"
                                    onClick={() => {
                                        if (confirming !== token.id) {
                                            setConfirming(token.id);

                                            return;
                                        }

                                        router.delete(destroy(token.id));
                                    }}
                                    data-test="token-revoke"
                                >
                                    {confirming === token.id
                                        ? t('tokens.revoke_confirm')
                                        : t('tokens.revoke')}
                                </Button>
                            </div>
                        ))}
                    </div>
                )}
            </div>
        </>
    );
}

Tokens.layout = {
    breadcrumbs: [{ title: 'tokens.title', href: index() }],
};
