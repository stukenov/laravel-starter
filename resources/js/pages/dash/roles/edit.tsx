import { Form, Head, router } from '@inertiajs/react';
import { useState } from 'react';
import RoleController from '@/actions/App/Http/Controllers/Dash/RoleController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useTranslations } from '@/hooks/use-translations';
import { destroy, index } from '@/routes/dash/roles';

type Props = {
    role: { id: string; name: string; is_protected: boolean };
    abilities: string[];
    granted: string[];
};

/**
 * The permission matrix is a large form, so it gets a page of its own
 * rather than a dialog. See the list-versus-form rule in AGENTS.md.
 *
 * Ability names contain a dot, which Laravel reads as a nested path in a
 * translation key — so the label lookup swaps it for an underscore.
 */
export default function RoleEdit({ role, abilities, granted }: Props) {
    const t = useTranslations();

    // Two clicks to delete. It is the only irreversible control here and
    // it sat next to Cancel, 44px under a thumb, one tap from gone.
    // This is a page, mounted afresh for each role, so nothing has to
    // disarm it — leaving the edit screen is what resets it.
    const [confirming, setConfirming] = useState(false);

    const label = (ability: string) =>
        t(`roles.abilities.${ability.replace('.', '_')}`);

    return (
        <>
            <Head title={role.name} />

            <div className="density-panel mx-auto max-w-2xl space-y-6 p-4">
                <Heading
                    variant="small"
                    title={t('roles.edit')}
                    description={t('roles.description')}
                />

                <Form
                    {...RoleController.update.form(role.id)}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid max-w-sm gap-1.5">
                                <Label htmlFor="name">{t('roles.name')}</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    defaultValue={role.name}
                                    data-test="role-name"
                                />
                                <p className="text-xs text-muted-foreground">
                                    {t('roles.name_hint')}
                                </p>
                                <InputError message={errors.name} />
                            </div>

                            <div className="space-y-2">
                                <p className="text-[15px] font-semibold">
                                    {t('roles.permissions')}
                                </p>

                                <div className="overflow-hidden rounded-md border">
                                    {abilities.map((ability) => (
                                        <label
                                            key={ability}
                                            className="flex items-center gap-3 border-b px-3 py-2 last:border-b-0 hover:bg-muted/40"
                                            data-test="ability-row"
                                        >
                                            <Checkbox
                                                name="permissions[]"
                                                value={ability}
                                                defaultChecked={granted.includes(
                                                    ability,
                                                )}
                                            />
                                            <span className="flex-1">
                                                {label(ability)}
                                            </span>
                                            <code className="text-xs text-muted-foreground">
                                                {ability}
                                            </code>
                                        </label>
                                    ))}
                                </div>
                            </div>

                            <div className="flex items-center gap-2">
                                <Button
                                    size="sm"
                                    disabled={processing}
                                    data-test="role-save"
                                >
                                    {t('roles.save')}
                                </Button>

                                {role.is_protected ? (
                                    <Badge variant="outline">
                                        {t('roles.protected_hint')}
                                    </Badge>
                                ) : (
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        size="sm"
                                        onClick={() => {
                                            if (!confirming) {
                                                setConfirming(true);

                                                return;
                                            }

                                            router.delete(destroy(role.id));
                                        }}
                                        data-test="role-delete"
                                    >
                                        {confirming
                                            ? t('roles.delete_confirm')
                                            : t('roles.delete')}
                                    </Button>
                                )}
                            </div>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

RoleEdit.layout = {
    breadcrumbs: [{ title: 'roles.title', href: index() }],
};
