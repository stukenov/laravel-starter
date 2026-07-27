<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Сессия истекла</title>
    {{-- RULE(errors): no @vite and not a single external link.
         This file must work when nothing else does: the SSR process is
         down, no build exists, the database is unreachable. Hence the
         system font and inline styles — and so it must stay.
         See AGENTS.md --}}
    <style>
        body {
            margin: 0; min-height: 100svh; display: grid; place-items: center;
            font-family: system-ui, -apple-system, sans-serif;
            color: #18181b; background: #fff;
        }
        main { text-align: center; padding: 1.5rem; max-width: 28rem; }
        .code { font-size: .875rem; color: #71717a; margin: 0 0 .5rem; }
        h1 { font-size: 1.5rem; margin: 0 0 .5rem; font-weight: 600; }
        .text { color: #71717a; margin: 0 0 1.5rem; }
        a {
            display: inline-block; padding: .5rem 1rem; border-radius: .625rem;
            background: #18181b; color: #fff; text-decoration: none;
            font-size: .875rem; font-weight: 500;
        }
    </style>
</head>
<body>
    <main>
        <p class="code">419</p>
        <h1>Сессия истекла</h1>
        <p class="text">Страница была открыта слишком долго. Войдите заново.</p>
        <a href="/">На главную</a>
    </main>
</body>
</html>
