<?php

// Correct data
it('uses Inter, not the starter default', function () {
    $config = file_get_contents(base_path('vite.config.ts'));
    $css = file_get_contents(resource_path('css/app.css'));

    expect($config)->toContain("'Inter'")
        ->and($config)->not->toContain('Instrument Sans')
        ->and($css)->toContain("'Inter'")
        ->and($css)->not->toContain('Instrument Sans');
});

it('requests both cyrillic subsets', function () {
    // Казахские буквы разбросаны по двум подмножествам:
    // і (U+0456) и ұ (U+04B1) — в cyrillic,
    // ә ғ қ ң ө ү һ — в cyrillic-ext. Нужны оба.
    $config = file_get_contents(base_path('vite.config.ts'));

    expect($config)->toContain("'cyrillic'")
        ->and($config)->toContain("'cyrillic-ext'");
});

// Wrong data
it('serves the font from our own domain', function () {
    $manifest = public_path('build/fonts-manifest.json');

    expect($manifest)->toBeFile();

    $contents = file_get_contents($manifest);

    // Ни одного абсолютного адреса: шрифт скачан на сборке и лежит у нас.
    expect($contents)->not->toContain('https://')
        ->and($contents)->not->toContain('http://')
        ->and($contents)->toContain('/build/assets/');
})->skip(
    fn () => ! file_exists(public_path('build/fonts-manifest.json')),
    'сборка не выполнена: npm run build',
);

it('covers every kazakh letter across the declared ranges', function () {
    // Настоящая проверка требования «шрифт с казахскими буквами».
    // Разобрано по файлам шрифта через fontTools: семь букв живут
    // в cyrillic-ext, а ұ и і — в cyrillic. Уберёшь одно подмножество —
    // часть букв молча подменится системным шрифтом, и это заметит
    // только казахскоязычный пользователь.
    $letters = [
        'ә' => 0x04D9, 'ғ' => 0x0493, 'қ' => 0x049B,
        'ң' => 0x04A3, 'ө' => 0x04E9, 'ұ' => 0x04B1,
        'ү' => 0x04AF, 'һ' => 0x04BB, 'і' => 0x0456,
    ];

    $manifest = json_decode(file_get_contents(public_path('build/fonts-manifest.json')), true);
    $css = $manifest['style']['familyStyles']['inter'] ?? '';

    expect($css)->not->toBeEmpty('в манифесте нет семейства inter');

    preg_match_all('/unicode-range:\s*([^;]+);/', $css, $matches);

    $covered = [];

    foreach ($matches[1] as $declaration) {
        foreach (explode(',', $declaration) as $part) {
            $part = trim($part);

            if (preg_match('/^U\+([0-9A-Fa-f]+)-([0-9A-Fa-f]+)$/', $part, $range)) {
                $covered[] = [hexdec($range[1]), hexdec($range[2])];
            } elseif (preg_match('/^U\+([0-9A-Fa-f]+)$/', $part, $single)) {
                $covered[] = [hexdec($single[1]), hexdec($single[1])];
            }
        }
    }

    $missing = [];

    foreach ($letters as $letter => $codepoint) {
        $found = false;

        foreach ($covered as [$from, $to]) {
            if ($codepoint >= $from && $codepoint <= $to) {
                $found = true;
                break;
            }
        }

        if (! $found) {
            $missing[] = sprintf('%s (U+%04X)', $letter, $codepoint);
        }
    }

    expect($missing)->toBeEmpty();
})->skip(
    fn () => ! file_exists(public_path('build/fonts-manifest.json')),
    'сборка не выполнена: npm run build',
);

// Empty data
it('declares a fallback family', function () {
    expect(file_get_contents(resource_path('css/app.css')))
        ->toContain('system-ui');
});
