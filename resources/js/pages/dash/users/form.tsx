import { Form, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import UserController from '@/actions/App/Http/Controllers/Dash/UserController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { destroy } from '@/routes/dash/users';

export type UserRow = {
    id: string;
    name: string;
    email: string;
    locale: string;
    timezone: string;
    email_verified_at: string | null;
    created_at: string | null;
    roles: string[];
};

type Props = {
    user: UserRow | null;
    open: boolean;
    timezones: string[];
    roles: string[];
    canManageRoles: boolean;
    onClose: () => void;
};

/**
 * One dialog for both creating and editing: the fields are identical and
 * two near-copies would drift apart. Which action runs is decided by
 * whether a user was passed in.
 */
export default function UserForm({
    user,
    open,
    timezones,
    roles,
    canManageRoles,
    onClose,
}: Props) {
    const t = useTranslations();

    // Two clicks to delete. It is the only irreversible control here and
    // it sat next to Cancel, 44px under a thumb, one tap from gone.
    // Reopening disarms it, so a form opened later never starts one click
    // away from removing somebody.
    const [confirming, setConfirming] = useState(false);

    const { locales } = usePage().props;

    const action = user
        ? UserController.update.form(user.id)
        : UserController.store.form();

    return (
        <Dialog
            open={open}
            onOpenChange={(next) => {
                setConfirming(false);

                if (!next) {
                    onClose();
                }
            }}
        >
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

                            {/* RULE(mobile): two columns at 390px is two
                                columns of 175px, and a language name does
                                not fit in one. Side by side from sm up,
                                stacked below it. See AGENTS.md. */}
                            <div className="grid gap-3 sm:grid-cols-2">
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

                            {/* Handing out a role is a different decision
                                from editing a name, so the field only
                                appears for somebody who may make it. The
                                server checks the same thing — this is
                                only the half a person can see. */}
                            {canManageRoles && roles.length > 0 && (
                                <div className="grid gap-2">
                                    {/* Says the field was on screen.
                                        Unchecked boxes send nothing, so
                                        without this "no roles" and "no
                                        roles field" look the same and
                                        editing a name would strip
                                        somebody's access. */}
                                    <input
                                        type="hidden"
                                        name="roles_submitted"
                                        value="1"
                                    />
                                    <Label>{t('users.roles')}</Label>
                                    <div className="flex flex-wrap gap-x-4 gap-y-2">
                                        {roles.map((role) => (
                                            <label
                                                key={role}
                                                className="flex min-h-11 items-center gap-2"
                                                data-test="user-role"
                                            >
                                                <Checkbox
                                                    name="roles[]"
                                                    value={role}
                                                    defaultChecked={
                                                        user?.roles?.includes(
                                                            role,
                                                        ) ?? false
                                                    }
                                                />
                                                {role}
                                            </label>
                                        ))}
                                    </div>
                                    <InputError message={errors.roles} />
                                </div>
                            )}

                            <DialogFooter className="sm:justify-between">
                                {/* The route, the controller and its
                                    tests all existed; nothing on screen
                                    reached them. An account could be
                                    created and edited and never removed.
                                    Only when editing — there is nothing
                                    to delete while creating. */}
                                {user ? (
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        size="sm"
                                        onClick={() => {
                                            onClose();

                                            if (!confirming) {
                                                setConfirming(true);

                                                return;
                                            }

                                            router.delete(destroy(user.id));
                                        }}
                                        data-test="user-delete"
                                    >
                                        {confirming
                                            ? t('users.delete_confirm')
                                            : t('users.delete')}
                                    </Button>
                                ) : (
                                    <span />
                                )}

                                <div className="flex gap-2">
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
                                </div>
                            </DialogFooter>
                        </>
                    )}
                </Form>
            </DialogContent>
        </Dialog>
    );
}
