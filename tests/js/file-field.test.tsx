import { render, screen } from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import { describe, expect, it } from 'vitest';

/**
 * RULE(files): the column holds a path, the disk holds the file — AGENTS.md.
 *
 * Three states, and the middle one is why this is a component rather than
 * an `<input type="file">`. Nothing attached: pick a file. Something
 * attached: a link and a way to remove it. Removing: a hidden empty
 * value, because an untouched file input submits nothing at all — and
 * "nothing" has to keep meaning "leave it alone", or every save without a
 * new file would wipe the old one.
 */
const { default: FileField } = await import('@/components/file-field');

const hiddenValue = (container: HTMLElement) =>
    container.querySelector<HTMLInputElement>('input[type="hidden"]')?.value;

describe('the file field', () => {
    // ── Correct data ─────────────────────────────────────────────────

    it('offers a way to choose a file', () => {
        render(<FileField name="attachment" data-test="probe-attachment" />);

        expect(screen.getByTestId('probe-attachment')).toHaveAttribute(
            'type',
            'file',
        );
    });

    it('links to what is already attached', () => {
        render(
            <FileField
                name="attachment"
                current="files/probes/019f.pdf"
                href="/dash/files/probes/1/attachment"
                data-test="probe-attachment"
            />,
        );

        const link = screen.getByTestId('probe-attachment-current');

        expect(link).toHaveAttribute('href', '/dash/files/probes/1/attachment');
        // The stored name, not the whole path: a person recognises the
        // file, not the folder it landed in.
        expect(link).toHaveTextContent('019f.pdf');
    });

    it('restricts the picker for an image field', () => {
        render(
            <FileField
                name="photo"
                accept=".jpg,.png"
                data-test="probe-photo"
            />,
        );

        expect(screen.getByTestId('probe-photo')).toHaveAttribute(
            'accept',
            '.jpg,.png',
        );
    });

    // ── Wrong data ───────────────────────────────────────────────────

    it('sends an empty value only once removal is asked for', async () => {
        // The whole reason the component exists. Without the hidden
        // value the server hears nothing and leaves the file alone,
        // which is right for an untouched form and wrong for this.
        const user = userEvent.setup();

        const { container } = render(
            <FileField
                name="attachment"
                current="files/probes/019f.pdf"
                href="/x"
                data-test="probe-attachment"
            />,
        );

        expect(hiddenValue(container)).toBeUndefined();

        await user.click(screen.getByTestId('probe-attachment-remove'));

        expect(hiddenValue(container)).toBe('');
        expect(
            screen.queryByTestId('probe-attachment-current'),
        ).not.toBeInTheDocument();
    });

    it('offers no removal when there is nothing to remove', () => {
        render(<FileField name="attachment" data-test="probe-attachment" />);

        expect(
            screen.queryByTestId('probe-attachment-remove'),
        ).not.toBeInTheDocument();
    });

    // ── Empty data ───────────────────────────────────────────────────

    it('sends nothing at all for an untouched empty field', () => {
        // An untouched form must not clear a file it never showed.
        const { container } = render(
            <FileField name="attachment" data-test="probe-attachment" />,
        );

        expect(hiddenValue(container)).toBeUndefined();
    });

    it('shows no link for a null value', () => {
        render(
            <FileField
                name="attachment"
                current={null}
                data-test="probe-attachment"
            />,
        );

        expect(
            screen.queryByTestId('probe-attachment-current'),
        ).not.toBeInTheDocument();
    });
});
