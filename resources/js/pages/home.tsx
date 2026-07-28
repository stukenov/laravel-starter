import { Head, usePage } from '@inertiajs/react';
import PublicLayout from '@/layouts/public-layout';
import { useTranslations } from '@/hooks/use-translations';

/**
 * The public home page.
 *
 * The chrome is real — header, locale switcher, footer, legal links — and
 * the body is left to the project. Placeholder marketing copy is demo
 * content, and demo content is the thing nobody remembers to delete.
 */
export default function Home() {
    const { name } = usePage().props;
    const t = useTranslations();

    return (
        <PublicLayout>
            {/* RULE(seo): a title and description per locale — AGENTS.md.
                The title is deliberately the application name: on a home
                page that is correct. The description was missing on every
                public page, which is the half of the rule that matters —
                a page with no description gets whatever the search engine
                scrapes off it. */}
            <Head title="">
                <meta name="description" content={t('seo.home')} />
            </Head>
            <h1
                className="text-3xl font-semibold tracking-tight"
                data-test="home-title"
            >
                {name as string}
            </h1>
        </PublicLayout>
    );
}
