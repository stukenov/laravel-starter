import { Form, Head, Link } from '@inertiajs/react';
import RoleController from '@/actions/App/Http/Controllers/Dash/RoleController';
import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
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
import { Pagination, type Paginated } from '@/components/pagination';
import { useTranslations } from '@/hooks/use-translations';
import { edit, index } from '@/routes/dash/roles';
import { useState } from 'react';

type Role = {
    id: string;
    name: string;
    permissions_count: number;
    users_count: number;
    is_protected: boolean;
};

type Props = {
    roles: Paginated<Role>;
};

/**
 * The list creates in a dialog because a name is one field. Editing opens
 * its own page: the permission matrix is a large form. See AGENTS.md.
 */
export default function RolesIndex({ roles }: Props) {
    const t = useTranslations();
    const [creating, setCreating] = useState(false);

    return (
        <>
            <Head title={t('roles.title')} />

            <div className="density-panel space-y-4 p-4">
                <div className="flex items-center justify-between gap-3">
                    <div>
                        <h1 className="text-base font-semibold">
                            {t('roles.title')}
                        </h1>
                        <p className="text-muted-foreground">
                            {t('roles.description')}
                        </p>
                    </div>

                    <Button
                        size="sm"
                        onClick={() => setCreating(true)}
                        data-test="roles-add"
                    >
                        {t('roles.add')}
                    </Button>
                </div>

                {roles.data.length === 0 ? (
                    <div
                        className="rounded-md border py-12 text-center text-muted-foreground"
                        data-test="roles-empty"
                    >
                        {t('roles.empty')}
                    </div>
                ) : (
                    <div className="overflow-hidden rounded-md border">
                        <div className="flex bg-muted/40 px-3 py-1.5 text-[11px] font-semibold tracking-wide text-muted-foreground uppercase">
                            <span className="flex-1">{t('roles.name')}</span>
                            <span className="w-28">
                                {t('roles.permissions_count')}
                            </span>
                            <span className="w-28">
                                {t('roles.users_count')}
                            </span>
                        </div>

                        {roles.data.map((role) => (
                            <Link
                                key={role.id}
                                href={edit(role.id)}
                                className="flex items-center border-t px-3 py-2 hover:bg-muted/40"
                                data-test="roles-row"
                            >
                                <span className="flex flex-1 items-center gap-2">
                                    {role.name}
                                    {role.is_protected && (
                                        <Badge variant="outline">
                                            {t('roles.protected')}
                                        </Badge>
                                    )}
                                </span>
                                <span className="w-28 text-muted-foreground">
                                    {role.permissions_count}
                                </span>
                                <span className="w-28 text-muted-foreground">
                                    {role.users_count}
                                </span>
                            </Link>
                        ))}
                    </div>
                )}

                <Pagination page={roles} />
            </div>

            <Dialog
                open={creating}
                onOpenChange={(next) => !next && setCreating(false)}
            >
                <DialogContent className="density-panel">
                    <DialogHeader>
                        <DialogTitle>{t('roles.add')}</DialogTitle>
                        <DialogDescription>
                            {t('roles.name_hint')}
                        </DialogDescription>
                    </DialogHeader>

                    <Form
                        {...RoleController.store.form()}
                        options={{ preserveScroll: true }}
                        onSuccess={() => setCreating(false)}
                        className="space-y-4"
                    >
                        {({ errors, processing }) => (
                            <>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="name">
                                        {t('roles.name')}
                                    </Label>
                                    <Input
                                        id="name"
                                        name="name"
                                        data-test="role-name"
                                    />
                                    <InputError message={errors.name} />
                                </div>

                                <DialogFooter>
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        size="sm"
                                        onClick={() => setCreating(false)}
                                    >
                                        {t('roles.cancel')}
                                    </Button>
                                    <Button
                                        size="sm"
                                        disabled={processing}
                                        data-test="role-save"
                                    >
                                        {t('roles.save')}
                                    </Button>
                                </DialogFooter>
                            </>
                        )}
                    </Form>
                </DialogContent>
            </Dialog>
        </>
    );
}

RolesIndex.layout = {
    breadcrumbs: [{ title: 'roles.title', href: index() }],
};
