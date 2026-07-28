import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { useTranslations } from '@/hooks/use-translations';
import { store } from '@/routes/password/confirm';

export default function ConfirmPassword() {
    const t = useTranslations();

    return (
        <>
            <Head title={t('auth.confirm_title')} />

            <Form {...store.form()} resetOnSuccess={['password']}>
                {({ processing, errors }) => (
                    <div className="space-y-6">
                        <div className="grid gap-2">
                            <Label htmlFor="password">
                                {t('auth.password')}
                            </Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                placeholder={t('auth.password_placeholder')}
                                autoComplete="current-password"
                                autoFocus
                                data-test="confirm-password-input"
                            />

                            <InputError message={errors.password} />
                        </div>

                        <div className="flex items-center">
                            <Button
                                className="w-full"
                                disabled={processing}
                                data-test="confirm-password-button"
                            >
                                {processing && <Spinner />}
                                {t('auth.confirm_submit')}
                            </Button>
                        </div>
                    </div>
                )}
            </Form>
        </>
    );
}

ConfirmPassword.layout = {
    titleKey: 'auth.confirm_title',
    descriptionKey: 'auth.confirm_description',
};
