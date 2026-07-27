<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- RULE(theme): light only. Neither system-theme detection nor a
             class on <html> belongs here — AGENTS.md --}}
        <style>
            html { background-color: oklch(1 0 0); }
        </style>

        {{-- Placeholder mark, replace it when cloning — AGENTS.md.
             The .ico is the fallback for browsers that ignore SVG icons,
             the touch icon is what iOS puts on the home screen. --}}
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="icon" href="/favicon.ico" sizes="48x48">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        {{-- The font arrives as an inline style block: files are fetched at
             build time and served from our own domain. No external requests
             are possible here. --}}
        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ config('app.name') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
