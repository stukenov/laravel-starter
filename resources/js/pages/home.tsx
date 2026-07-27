import { Head, usePage } from '@inertiajs/react';
import PublicLayout from '@/layouts/public-layout';

/**
 * The public home page.
 *
 * The chrome is real — header, locale switcher, footer, legal links — and
 * the body is left to the project. Placeholder marketing copy is demo
 * content, and demo content is the thing nobody remembers to delete.
 */
export default function Home() {
    const { name } = usePage().props;

    return (
        <PublicLayout>
            <Head title="" />
            <h1
                className="text-3xl font-semibold tracking-tight"
                data-test="home-title"
            >
                {name as string}
            </h1>
        </PublicLayout>
    );
}
