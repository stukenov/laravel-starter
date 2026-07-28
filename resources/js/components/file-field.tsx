import { X } from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';

/**
 * RULE(files): the column holds a path, the disk holds the file — see AGENTS.md.
 *
 * Three states, and the middle one is the reason this is a component
 * rather than an `<input type="file">`. Nothing attached: pick a file.
 * Something attached: a link to it and a way to remove it. Removing:
 * a hidden empty value, so the server hears "clear this" rather than
 * hearing nothing — an untouched file input submits nothing at all, and
 * "nothing" has to keep meaning "leave it alone".
 */
type Props = {
    name: string;
    current?: string | null;
    href?: string | null;
    accept?: string;
    id?: string;
    'data-test'?: string;
};

export default function FileField({
    name,
    current,
    href,
    accept,
    id,
    'data-test': dataTest,
}: Props) {
    const [cleared, setCleared] = useState(false);
    const attached = Boolean(current) && !cleared;

    return (
        <div className="flex flex-col gap-2">
            {attached && (
                <div className="flex min-h-11 items-center gap-2 sm:min-h-0">
                    <a
                        href={href ?? '#'}
                        className="truncate text-sm underline underline-offset-4"
                        data-test={dataTest ? `${dataTest}-current` : undefined}
                    >
                        {current?.split('/').pop()}
                    </a>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        aria-label="remove"
                        className="size-11 p-0 sm:size-7"
                        onClick={() => setCleared(true)}
                        data-test={dataTest ? `${dataTest}-remove` : undefined}
                    >
                        <X className="size-4" />
                    </Button>
                </div>
            )}

            {/* An untouched file input submits nothing, so "clear it" needs
                a value of its own. Sent only while the field is cleared and
                no replacement has been chosen. */}
            {cleared && <input type="hidden" name={name} value="" readOnly />}

            <Input
                type="file"
                id={id}
                name={name}
                accept={accept}
                className="min-h-11 py-1.5 sm:min-h-9"
                data-test={dataTest}
            />
        </div>
    );
}
