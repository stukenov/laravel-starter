import { Head } from '@inertiajs/react';

/**
 * Skeleton of the public home page.
 *
 * Deliberately empty: placeholder copy is demo content that nobody
 * remembers to remove. A real project fills it in.
 */
export default function Home() {
    return (
        <>
            <Head title="" />
            <main className="mx-auto flex min-h-svh max-w-3xl flex-col justify-center px-6" />
        </>
    );
}
