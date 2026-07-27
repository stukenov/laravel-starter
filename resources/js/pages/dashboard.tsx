import { Head } from '@inertiajs/react';
import { dashboard } from '@/routes';

/**
 * Каркас сводки.
 *
 * Плитки-заглушки из стартера убраны: это демо-контент, который потом
 * забывают вычистить. Наполняется на конкретном проекте.
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
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
