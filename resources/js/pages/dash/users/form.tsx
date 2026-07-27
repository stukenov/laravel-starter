import { Form, usePage } from '@inertiajs/react';
import UserController from '@/actions/App/Http/Controllers/Dash/UserController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useTranslations } from '@/hooks/use-translations';

export type UserRow = {
    id: string;
    name: string;
    email: string;
    locale: string;
    timezone: string;
    email_verified_at: string | null;
    created_at: string | null;
};

type Props = {
    user: UserRow | null;
    open: boolean;
    timezones: string[];
    onClose: () => void;
};

/**
 * One dialog for both creating and editing: the fields are identical and
 * two near-copies would drift apart. Which action runs is decided by
 * whether a user was passed in.
 */
export default function UserForm({ user, open, timezones, onClose }: Props) {
    const t = useTranslations();
    const { locales } = usePage().props;

    const action = user
        ? UserController.update.form(user.id)
        : UserController.store.form();

    return (
        <Dialog open={open} onOpenChange={(next) => !next && onClose()}>
            <DialogContent className="density-panel">
                <DialogHeader>
                    <DialogTitle>
                        {user ? t('users.edit') : t('users.add')}
                    </DialogTitle>
                    <DialogDescription>
                        {t('users.description')}
                    </DialogDescription>
                </DialogHeader>

                <Form
                    {...action}
                    options={{ preserveScroll: true }}
                    onSuccess={onClose}
                    className="space-y-4"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-1.5">
                                <Label htmlFor="name">{t('users.name')}</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={user?.name ?? ''}
                                    data-test="user-name"
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="email">
                                    {t('users.email')}
                                </Label>
                                <Input
                                    id="email"
                                    name="email"
                                    type="email"
                                    defaultValue={user?.email ?? ''}
                                    data-test="user-email"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-1.5">
                                <Label htmlFor="password">
                                    {t('users.password')}
                                </Label>
                                <Input
                                    id="password"
                                    name="password"
                                    type="password"
                                    autoComplete="new-password"
                                    data-test="user-password"
                                />
                                {user && (
                                    <p className="text-xs text-muted-foreground">
                                        {t('users.password_hint')}
                                    </p>
                                )}
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid grid-cols-2 gap-3">
                                <div className="grid gap-1.5">
                                    <Label htmlFor="locale">
                                        {t('users.locale')}
                                    </Label>
                                    <Select
                                        name="locale"
                                        defaultValue={user?.locale ?? 'ru'}
                                    >
                                        <SelectTrigger id="locale">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {locales.map((code) => (
                                                <SelectItem
                                                    key={code}
                                                    value={code}
                                                >
                                                    {code}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.locale} />
                                </div>

                                <div className="grid gap-1.5">
                                    <Label htmlFor="timezone">
                                        {t('users.timezone')}
                                    </Label>
                                    <Select
                                        name="timezone"
                                        defaultValue={
                                            user?.timezone ?? 'Asia/Almaty'
                                        }
                                    >
                                        <SelectTrigger id="timezone">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {timezones.map((zone) => (
                                                <SelectItem
                                                    key={zone}
                                                    value={zone}
                                                >
                                                    {zone}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.timezone} />
                                </div>
                            </div>

                            <DialogFooter>
                                <Button
                                    type="button"
                                    variant="secondary"
                                    size="sm"
                                    onClick={onClose}
                                >
                                    {t('users.cancel')}
                                </Button>
                                <Button
                                    size="sm"
                                    disabled={processing}
                                    data-test="user-save"
                                >
                                    {t('users.save')}
                                </Button>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
