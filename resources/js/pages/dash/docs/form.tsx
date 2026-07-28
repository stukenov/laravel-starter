import { Form, router } from '@inertiajs/react';
import { useState } from 'react';
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
import { useTranslations } from '@/hooks/use-translations';
import DocController from '@/actions/App/Http/Controllers/Dash/DocController';
import { destroy } from '@/routes/dash/docs';

export type DocRow = {
    id: string;
    title: string;
    attachment: string | null;
    created_at: string | null;
};

type Props = {
    record: DocRow | null;
    open: boolean;

    onClose: () => void;
};

/**
 * One dialog for both creating and editing: the fields are identical and
 * two near-copies would drift apart.
 */
export default function RecordForm({ record, open, onClose }: Props) {
    const t = useTranslations();

    // Armed by the first click on Delete, disarmed whenever the dialog
    // opens or closes — so a dialog reopened later never starts one click
    // away from removing something. Reset where it happens rather than in
    // an effect: calling setState from one triggers a cascading render,
    // and the lint rule says so.
    const [confirming, setConfirming] = useState(false);

    const action = record
        ? DocController.update.form(record.id)
        : DocController.store.form();

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
                        {record ? t('docs.edit') : t('docs.add')}
                    </DialogTitle>
                    <DialogDescription>
                        {t('docs.description')}
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
                                <Label htmlFor="title">
                                    {t('docs.fields.title')}
                                </Label>
                                <Input
                                    id="title"
                                    name="title"
                                    defaultValue={String(record?.title ?? '')}
                                    data-test="doc-title"
                                />
                                <InputError message={errors.title} />
                            </div>
                            <div className="grid gap-1.5">
                                <Label htmlFor="attachment">
                                    {t('docs.fields.attachment')}
                                </Label>
                                <Input
                                    id="attachment"
                                    name="attachment"
                                    defaultValue={String(
                                        record?.attachment ?? '',
                                    )}
                                    data-test="doc-attachment"
                                />
                                <InputError message={errors.attachment} />
                            </div>

                            <DialogFooter className="sm:justify-between">
                                {/* The generated controller has a
                                    destroy action, a route and a test
                                    for it, and nothing on screen reached
                                    any of them: a record could be made
                                    and edited and never removed. Only
                                    when editing — there is nothing to
                                    delete while creating. */}
                                {/* Two clicks, not one. Deleting is the
                                    only irreversible thing on this
                                    screen and it used to happen on a
                                    single click, next to Cancel, at 44px
                                    under a thumb. The second click says
                                    what it will do. */}
                                {record ? (
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        size="sm"
                                        className="min-h-11 sm:min-h-8"
                                        onClick={() => {
                                            if (!confirming) {
                                                setConfirming(true);

                                                return;
                                            }

                                            onClose();
                                            router.delete(destroy(record.id));
                                        }}
                                        data-test="doc-delete"
                                    >
                                        {confirming
                                            ? t('docs.delete_confirm')
                                            : t('docs.delete')}
                                    </Button>
                                ) : (
                                    <span />
                                )}

                                {/* RULE(mobile): 44px under a finger,
                                    dense under a cursor. shadcn's `sm`
                                    is 32px, which is a comfortable
                                    target for a mouse and a miss for a
                                    thumb — and these are the two buttons
                                    that finish the job. */}
                                <div className="flex gap-2">
                                    <Button
                                        type="button"
                                        variant="secondary"
                                        size="sm"
                                        className="min-h-11 flex-1 sm:min-h-8 sm:flex-none"
                                        onClick={onClose}
                                    >
                                        {t('docs.cancel')}
                                    </Button>
                                    <Button
                                        size="sm"
                                        className="min-h-11 flex-1 sm:min-h-8 sm:flex-none"
                                        disabled={processing}
                                        data-test="doc-save"
                                    >
                                        {t('docs.save')}
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
