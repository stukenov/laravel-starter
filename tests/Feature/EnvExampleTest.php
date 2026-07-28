<?php

use Illuminate\Support\Facades\File;

/**
 * `.env.example` is the file a fresh clone copies, so a setting missing
 * from it is a setting nobody sets.
 *
 * AGENTS.md listed four variables under "replace when cloning" and not
 * one of them was in the example file. Every one fails silently when left
 * empty — Laravel's mail channel returns without sending and without
 * erroring, so a failing disk, queue or database alerts nobody at all.
 */
function envExample(): string
{
    return File::get(base_path('.env.example'));
}

// ── Correct data ─────────────────────────────────────────────────────

it('names every operational setting AGENTS.md says to replace', function () {
    $missing = collect([
        'HEALTH_TO_ADDRESS',
        'HEALTH_JSON_SECRET',
        'BACKUP_NOTIFICATION_EMAIL',
        'FAILED_JOB_EMAILS',
        'TRUSTED_PROXIES',
        'REGISTRATION_ENABLED',
    ])->reject(fn (string $name) => str_contains(envExample(), $name))->values();

    expect($missing->all())->toBe([]);
});

it('says what each of them does', function () {
    // A bare name with no comment is a name somebody leaves empty.
    expect(substr_count(envExample(), '#'))->toBeGreaterThan(10);
});

// ── Wrong data ───────────────────────────────────────────────────────

it('carries no real secret', function () {
    // An example file with a working key in it is how keys reach GitHub.
    expect(envExample())->toContain('APP_KEY=')
        ->and(preg_match('/APP_KEY=base64:.+/', envExample()))->toBe(0);
});

it('is written in English like everything else agents read', function () {
    expect(preg_match('/[а-яА-Я]/u', envExample()))->toBe(0);
});

// ── Empty data ───────────────────────────────────────────────────────

it('exists and is not empty', function () {
    expect(base_path('.env.example'))->toBeFile()
        ->and(strlen(envExample()))->toBeGreaterThan(500);
});
