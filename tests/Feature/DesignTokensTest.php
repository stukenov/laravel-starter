<?php

/*
 * RULE(design): токены — единственный источник вида — AGENTS.md.
 *
 * Эти проверки ловят регрессы, которые видно только глазами: текст того же
 * цвета, что и фон, или нейтральный элемент, окрасившийся акцентом.
 */

function tokens(): string
{
    return file_get_contents(resource_path('css/app.css'));
}

// Правильные данные
it('uses the apple system blue as accent', function () {
    expect(tokens())->toContain('#0071e3');
});

it('keeps the panel radius in the macOS range', function () {
    preg_match('/--radius:\s*([\d.]+)rem/', tokens(), $matches);

    $pixels = (float) ($matches[1] ?? 0) * 16;

    // Системные настройки macOS — 5–7px. Больше 10px это уже язык iOS,
    // который мы отвергли на макетах.
    expect($pixels)->toBeGreaterThanOrEqual(5)->toBeLessThanOrEqual(8);
});

it('defines both density scales', function () {
    expect(tokens())->toContain('.density-panel')
        ->and(tokens())->toContain('.density-public');
});

// Неправильные данные
it('never paints destructive text the colour of the background', function () {
    // Сообщение об ошибке красится через --destructive-foreground и лежит
    // на светлом фоне. Белый здесь делает его невидимым — разметка на месте,
    // а прочитать нельзя.
    preg_match('/--destructive-foreground:\s*([^;]+);/', tokens(), $matches);

    expect(trim($matches[1] ?? ''))->not->toBe('oklch(1 0 0)');
});

it('keeps neutral components independent of the accent', function () {
    // Скелетон загрузки — нейтральный элемент, и с ним два разных промаха.
    // На bg-primary он красится акцентом и читается как активное состояние.
    // На bg-muted он почти сливается с белой карточкой и пропадает совсем —
    // оба раза это видно только глазами, разметка на месте.
    $skeleton = file_get_contents(resource_path('js/components/ui/skeleton.tsx'));

    expect($skeleton)->not->toContain('bg-primary')
        ->and($skeleton)->not->toContain('bg-muted');
});

it('has no dark theme block left', function () {
    expect(tokens())->not->toContain('.dark {');
});

// Пустые данные
it('leaves the accent as a single swappable line', function () {
    // Смысл в том, чтобы смена цвета под проект была правкой одной строки.
    $occurrences = substr_count(tokens(), '#0071e3');

    expect($occurrences)->toBe(1);
});
