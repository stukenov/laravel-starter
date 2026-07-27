import { Head } from '@inertiajs/react';
import { home as dashHome } from '@/routes/dash';

/**
 * Skeleton of the dashboard.
 *
 * The starter's placeholder tiles are gone: demo content that nobody
 * remembers to remove. A real project fills it in.
 */
export default function Dashboard() {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4" />
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'nav.dashboard',
            href: dashHome(),
        },
    ],
};
