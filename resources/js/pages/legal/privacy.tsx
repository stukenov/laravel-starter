import { Head } from '@inertiajs/react';

/**
 * Каркас политики конфиденциальности.
 *
 * Текст намеренно отсутствует: юридический документ пишет заказчик под
 * конкретный проект, а текст-заглушка рискует уехать в прод как настоящий.
 */
export default function Privacy() {
    return (
        <>
            <Head title="" />
            <main className="mx-auto max-w-2xl px-6 py-16" />
        </>
    );
}
