import type { HTMLAttributes } from 'react';
import { cn } from '@/lib/utils';

export default function InputError({
    message,
    className = '',
    ...props
}: HTMLAttributes<HTMLParagraphElement> & { message?: string }) {
    return message ? (
        // Marked so a test can find "a field was rejected" without
        // depending on the wording, which is translated three ways and
        // changes.
        <p
            {...props}
            data-test="input-error"
            className={cn('text-sm text-red-600', className)}
        >
            {message}
        </p>
    ) : null;
}
