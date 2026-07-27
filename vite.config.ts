import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
            // RULE(external): one font, local, with Cyrillic — AGENTS.md.
            //
            // The files are downloaded at build time and served from our
            // own domain; `@fonts` emits them as an inline style block
            // with a nonce. No external request happens at render time.
            //
            // Both Cyrillic subsets are required: і and ұ live in
            // `cyrillic`, the rest of the Kazakh letters in
            // `cyrillic-ext`. Drop either and some letters are silently
            // replaced by a system font.
            //
            // `preload: false` on purpose. Preloading is the default and
            // it defeats the point of `unicode-range`: the browser is
            // told to fetch all sixteen files before it knows which
            // letters the page contains. Measured — 306 KB on every page
            // in every language, including English, which needs no
            // Cyrillic at all. Without it the browser fetches the subsets
            // the text actually uses.
            fonts: [
                bunny('Inter', {
                    weights: [400, 500, 600, 700],
                    subsets: ['latin', 'latin-ext', 'cyrillic', 'cyrillic-ext'],
                    preload: false,
                }),
            ],
        }),
        inertia(),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
});
