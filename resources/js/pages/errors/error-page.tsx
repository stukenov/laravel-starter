import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';

type Props = {
    status: number;
    title: string;
    description: string;
    home: string;
};

/**
 * RULE(errors): an ordinary page, not a modal — AGENTS.md.
 *
 * Inertia shows the modal when the server answers with a non-Inertia
 * response. The handler in bootstrap/app.php removes the cause: every
 * handled status comes back as a proper page.
 */
export default function ErrorPage({ status, title, description, home }: Props) {
    return (
        <>
            <Head title={title} />

            <main className="mx-auto flex min-h-svh max-w-md flex-col items-center justify-center gap-4 px-6 text-center">
                <p
                    className="text-sm font-medium text-muted-foreground"
                    data-test="error-status"
                >
                    {status}
                </p>

                <h1 className="text-2xl font-semibold tracking-tight">
                    {title}
                </h1>

                <p className="text-muted-foreground">{description}</p>

                <Button asChild className="mt-2">
                    <Link href="/">{home}</Link>
                </Button>
            </main>
        </>
    );
}
