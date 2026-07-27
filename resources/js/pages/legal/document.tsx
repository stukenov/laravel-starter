import { Head } from '@inertiajs/react';
import { useTranslations } from '@/hooks/use-translations';
import PublicLayout from '@/layouts/public-layout';

/**
 * Shared body of the legal pages.
 *
 * The section headings ship, the wording does not. A legal document is
 * written per project and per jurisdiction, and plausible-looking filler
 * is worse than nothing: it can go to production unnoticed and read as a
 * real promise. Hence the unmissable notice under every empty section.
 *
 * Fill `legal.{document}.body.{section}` in `lang/{ru,kk,en}` — see the
 * "Replace when cloning" list in AGENTS.md.
 */
export default function LegalDocument({
    document,
    sections,
}: {
    document: 'privacy' | 'terms';
    sections: string[];
}) {
    const t = useTranslations();
    const title = t(`legal.${document}.title`);

    return (
        <PublicLayout>
            <Head title={title} />

            <article className="mx-auto max-w-2xl">
                <h1
                    className="text-3xl font-semibold tracking-tight"
                    data-test="legal-title"
                >
                    {title}
                </h1>

                <div className="mt-10 space-y-8">
                    {sections.map((section) => {
                        const body = t(`legal.${document}.body.${section}`);
                        const filled = body !== `legal.${document}.body.${section}`;

                        return (
                            <section key={section} data-test="legal-section">
                                <h2 className="font-semibold">
                                    {t(`legal.${document}.sections.${section}`)}
                                </h2>
                                <p
                                    className={
                                        filled
                                            ? 'mt-2 text-muted-foreground'
                                            : 'mt-2 text-destructive'
                                    }
                                    data-test={
                                        filled ? 'legal-body' : 'legal-unfilled'
                                    }
                                >
                                    {filled ? body : t('legal.unfilled')}
                                </p>
                            </section>
                        );
                    })}
                </div>
            </article>
        </PublicLayout>
    );
}
