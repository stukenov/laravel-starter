// Components
import { Form, Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { HoneypotFields } from '@/components/honeypot-fields';
import type { Honeypot } from '@/components/honeypot-fields';
import InputError from '@/components/input-error';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/hooks/use-translations';
import { login } from '@/routes';
import { email } from '@/routes/password';

type Props = {
    status?: string;
    honeypot: Honeypot;
};

export default function ForgotPassword({ status, honeypot }: Props) {
    const t = useTranslations();

    return (
        <>
            <Head title={t('auth.forgot_title')} />

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}

            <div className="space-y-6">
                <Form {...email.form()}>
                    {({ processing, errors }) => (
                        <>
                            <HoneypotFields honeypot={honeypot} />

                            <div className="grid gap-2">
                                <Label htmlFor="email">{t('auth.email')}</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    autoComplete="off"
                                    autoFocus
                                    placeholder={t('auth.email_placeholder')}
                                />

                                <InputError message={errors.email} />
                            </div>

                            <div className="my-6 flex items-center justify-start">
                                <Button
                                    className="w-full"
                                    disabled={processing}
                                    data-test="email-password-reset-link-button"
                                >
                                    {processing && (
                                        <LoaderCircle className="h-4 w-4 animate-spin" />
                                    )}
                                    {t('auth.send_reset_link')}
                                </Button>
                            </div>
                        </>
                    )}
                </Form>

                <div className="space-x-1 text-center text-sm text-muted-foreground">
                    <span>{t('auth.or_return_to')}</span>
                    <TextLink href={login()}>{t('auth.login')}</TextLink>
                </div>
            </div>
        </>
    );
}

ForgotPassword.layout = {
    titleKey: 'auth.forgot_title',
    descriptionKey: 'auth.forgot_description',
};
