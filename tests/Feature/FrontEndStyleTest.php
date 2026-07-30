<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * Two formatters were configured and neither was ever run.
 *
 * `npm run lint:check` reported twenty-one errors and prettier another
 * seventeen files, and the two lists only partly overlapped — so the
 * project shipped with a linter that did not pass and a formatter that
 * disagreed with it. Neither appeared in the documented commands, which
 * is how a script goes unrun for the life of a repository.
 *
 * They agree now, and both are in one script so that running half of it
 * is not an option.
 */

// ── Correct data ─────────────────────────────────────────────────────

it('runs both tools from one script', function () {
    // Half a format is worse than none: the two would take turns undoing
    // each other on every save.
    $scripts = json_decode(File::get(base_path('package.json')), true)['scripts'];

    expect($scripts['lint'])->toContain('prettier')
        ->and($scripts['lint'])->toContain('eslint')
        ->and($scripts['lint:check'])->toContain('prettier')
        ->and($scripts['lint:check'])->toContain('eslint');
});

it('names the command where somebody will look for it', function () {
    expect(File::get(base_path('AGENTS.md')))->toContain('npm run lint');
});

// ── Wrong data ───────────────────────────────────────────────────────

it('keeps the checking variant free of --fix', function () {
    // A check that repairs what it finds always passes.
    $scripts = json_decode(File::get(base_path('package.json')), true)['scripts'];

    expect($scripts['lint:check'])->not->toContain('--fix')
        ->and($scripts['lint:check'])->not->toContain('--write');
});

// ── Empty data ───────────────────────────────────────────────────────

it('has an eslint configuration to run at all', function () {
    expect(base_path('eslint.config.js'))->toBeFile();
});
