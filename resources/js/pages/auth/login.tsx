import { Form, Head, usePage } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/hooks/use-translations';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

/**
 * No `tabIndex` anywhere on purpose.
 *
 * The form used to number its fields by hand, and the numbering was
 * wrong: "forgot your password" sits between the email and the password
 * on screen and carried `tabIndex={5}`, so somebody moving by keyboard
 * reached it *after* the sign-in button. The registration link carried
 * the same 5. A positive tabindex fights the document order instead of
 * following it, and the document order here is already what the eye sees.
 */
export default function Login({ status, canResetPassword }: Props) {
    const t = useTranslations();
    const { registrationEnabled, registerUrl } = usePage().props;

    return (
        <>
            <Head title={t('auth.login')} />

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-primary">
                    {status}
                </div>
            )}

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="email">{t('auth.email')}</Label>
                                <Input
                                    id="email"
                                    data-test="login-email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    autoComplete="email"
                                    placeholder={t('auth.email_placeholder')}
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <Label htmlFor="password">
                                        {t('auth.password')}
                                    </Label>
                                    {canResetPassword && (
                                        <TextLink
                                            href={request()}
                                            className="ml-auto text-sm"
                                        >
                                            {t('auth.forgot_password')}
                                        </TextLink>
                                    )}
                                </div>
                                <PasswordInput
                                    id="password"
                                    data-test="login-password"
                                    name="password"
                                    required
                                    autoComplete="current-password"
                                    placeholder={t('auth.password_placeholder')}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox id="remember" name="remember" />
                                <Label htmlFor="remember">
                                    {t('auth.remember')}
                                </Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-4 w-full"
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                {t('auth.login')}
                            </Button>
                        </div>

                        {/* REGISTRATION_ENABLED=false unregisters the
                            route, and this offered the link anyway — the
                            one public entrance to a closed application
                            ending in a 404.

                            The url comes from shared props rather than from
                            `@/routes`: with the route gone Wayfinder stops
                            exporting it, and a static import fails the
                            build even though this branch never runs. */}
                        {registrationEnabled && registerUrl !== null && (
                            <div
                                className="text-center text-sm text-muted-foreground"
                                data-test="login-signup"
                            >
                                {t('auth.no_account')}{' '}
                                <TextLink href={registerUrl}>
                                    {t('auth.sign_up')}
                                </TextLink>
                            </div>
                        )}
                    </>
                )}
            </Form>
        </>
    );
}

Login.layout = {
    titleKey: 'auth.login_title',
    descriptionKey: 'auth.login_description',
};
