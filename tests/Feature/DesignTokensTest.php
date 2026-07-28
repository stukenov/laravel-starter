<?php

/*
 * RULE(design): tokens are the single source of appearance — AGENTS.md.
 *
 * These checks catch regressions visible only to the eye: text the
 * same colour as its background, or a neutral element painted with
 * the accent colour.
 */

function tokens(): string
{
    return file_get_contents(resource_path('css/app.css'));
}

// Correct data
it('uses the apple system blue as accent', function () {
    expect(tokens())->toContain('#0071e3');
});

it('keeps the panel radius in the macOS range', function () {
    preg_match('/--radius:\s*([\d.]+)rem/', tokens(), $matches);

    $pixels = (float) ($matches[1] ?? 0) * 16;

    // macOS System Settings uses 5-7px. Anything over 10px is the iOS
    // language, which we rejected in the mockups.
    expect($pixels)->toBeGreaterThanOrEqual(5)->toBeLessThanOrEqual(8);
});

it('defines both density scales', function () {
    expect(tokens())->toContain('.density-panel')
        ->and(tokens())->toContain('.density-public');
});

// Wrong data
it('never paints destructive text the colour of the background', function () {
    // The error message is coloured via --destructive-foreground and
    // sits on a light background. White here makes it invisible — the
    // markup is there, but nobody can read it.
    preg_match('/--destructive-foreground:\s*([^;]+);/', tokens(), $matches);

    expect(trim($matches[1] ?? ''))->not->toBe('oklch(1 0 0)');
});

it('keeps neutral components independent of the accent', function () {
    // The loading skeleton is a neutral element, and there are two
    // different ways to get it wrong. On bg-primary it's painted with
    // the accent colour and reads as an active state. On bg-muted it
    // nearly vanishes into the white card — both times only visible
    // to the eye, the markup is there either way.
    $skeleton = file_get_contents(resource_path('js/components/ui/skeleton.tsx'));

    expect($skeleton)->not->toContain('bg-primary')
        ->and($skeleton)->not->toContain('bg-muted');
});

it('has no dark theme block left', function () {
    expect(tokens())->not->toContain('.dark {');
});

// Empty data
it('leaves the accent as a single swappable line', function () {
    // The point: swapping the colour for a new project is a one-line edit.
    $occurrences = substr_count(tokens(), '#0071e3');

    expect($occurrences)->toBe(1);
});
