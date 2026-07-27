import { Form, Head, usePage } from '@inertiajs/react';
import LocaleController from '@/actions/App/Http/Controllers/Settings/LocaleController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { formatDateTime } from '@/lib/datetime';
import { edit } from '@/routes/locale';

type Props = {
    timezones: string[];
};

const LOCALE_NAMES: Record<string, string> = {
    ru: 'Русский',
    kk: 'Қазақша',
    en: 'English',
};

export default function LocaleSettings({ timezones }: Props) {
    const { auth, locale, locales } = usePage().props;
    const timezone = (auth.user.timezone as string) ?? 'UTC';

    return (
        <>
            <Head title="Язык и время" />

            <div className="space-y-6">
                <Heading
                    variant="small"
                    title="Язык и время"
                    description="Язык интерфейса и часовой пояс, в котором показывается время"
                />

                <Form
                    {...LocaleController.update.form()}
                    options={{ preserveScroll: true }}
                    className="space-y-6"
                >
                    {({ errors, processing }) => (
                        <>
                            <div className="grid gap-2">
                                <Label htmlFor="locale">Язык</Label>

                                <Select name="locale" defaultValue={locale}>
                                    <SelectTrigger id="locale">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {locales.map((code) => (
                                            <SelectItem key={code} value={code}>
                                                {LOCALE_NAMES[code] ?? code}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                <InputError message={errors.locale} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="timezone">Часовой пояс</Label>

                                <Select name="timezone" defaultValue={timezone}>
                                    <SelectTrigger id="timezone">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {timezones.map((zone) => (
                                            <SelectItem key={zone} value={zone}>
                                                {zone}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>

                                <InputError message={errors.timezone} />

                                <p className="text-sm text-muted-foreground">
                                    Сейчас у вас{' '}
                                    <span data-test="current-time">
                                        {formatDateTime(
                                            new Date().toISOString(),
                                            timezone,
                                            locale,
                                        )}
                                    </span>
                                </p>
                            </div>

                            <Button
                                disabled={processing}
                                data-test="save-locale"
                            >
                                Сохранить
                            </Button>
                        </>
                    )}
                </Form>
            </div>
        </>
    );
}

LocaleSettings.layout = {
    breadcrumbs: [{ title: 'nav.locale', href: edit() }],
};
