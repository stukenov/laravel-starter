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
            /** Active UI language: ru | kk | en */
            locale: string;
            /** Every supported language, for the switcher */
            locales: string[];
            /** Flat translation dictionary, keyed 'nav.dashboard' */
            translations: Record<string, string>;
            /** False closes the sign-up route; the page must stop offering it */
            registrationEnabled: boolean;
            /** Null when registration is closed — see HandleInertiaRequests. */
            registerUrl: string | null;
            registerAction: string | null;
            sidebarOpen: boolean;
            [key: string]: unknown;
        };
    }
}
