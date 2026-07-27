import { Head } from '@inertiajs/react';

/**
 * Каркас публичной главной страницы.
 *
 * Намеренно пустая: тексты-заглушки — это демо-контент, который потом
 * забывают вычистить. Наполняется на конкретном проекте.
 */
export default function Home() {
    return (
        <>
            <Head title="" />
            <main className="mx-auto flex min-h-svh max-w-3xl flex-col justify-center px-6" />
        </>
    );
}
