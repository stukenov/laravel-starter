import type { Auth } from '@/types/auth';

declare module 'react' {
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    interface InputHTMLAttributes<T> {
        passwordrules?: string;
    }
}

declare module '@inertiajs/core' {
    export interface InertiaConfig {
        sharedPageProps: {
            name: string;
            auth: Auth;
            /** Текущий язык интерфейса: ru | kk | en */
            locale: string;
            /** Все поддерживаемые языки — для переключателя */
            locales: string[];
            /** Плоский словарь переводов: ключ 'nav.dashboard' */
            translations: Record<string, string>;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
