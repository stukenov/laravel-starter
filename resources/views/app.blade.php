<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- ПРАВИЛО(тема): только светлая. Здесь не должно появиться ни
             определения системной темы, ни класса на <html> — AGENTS.md --}}
        <style>
            html { background-color: oklch(1 0 0); }
        </style>

        <link rel="icon" href="/favicon.svg" type="image/svg+xml">

        {{-- Шрифт приходит встроенным блоком стилей: файлы скачаны на сборке
             и лежат на нашем домене. Внешних запросов здесь быть не может. --}}
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
