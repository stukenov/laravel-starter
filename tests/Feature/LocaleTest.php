<?php

use App\Models\User;
use App\Support\Locale\Locales;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

// Correct data
it('serves each supported locale', function (string $locale) {
    $this->get("/{$locale}")->assertOk();

    expect(app()->getLocale())->toBe($locale);
})->with(['ru', 'kk', 'en']);

it('shares translations with the front end', function () {
    $this->get('/ru')->assertInertia(fn ($page) => $page
        ->where('locale', 'ru')
        ->has('locales', 3)
        // The dictionary is flat: the key is literally 'auth.login'.
        // where() can't check that — there a dot means nesting — so
        // we pull the whole dictionary and look the key up directly.
        // Inertia hands this over as a Collection, not an array.
        ->where('translations', fn (Collection $t) => $t->get('auth.login') === 'Войти'),
    );
});

it('serves legal pages under every locale', function (string $locale) {
    $this->get("/{$locale}/privacy")->assertOk();
    $this->get("/{$locale}/terms")->assertOk();
})->with(['ru', 'kk', 'en']);

it('takes the locale from the profile inside the cabinet', function () {
    $user = User::factory()->create(['locale' => 'kk']);

    $this->actingAs($user)->get(route('dash.home'))->assertOk();

    expect(app()->getLocale())->toBe('kk');
});

// Wrong data
it('returns 404 for an unsupported locale', function () {
    $this->get('/de')->assertNotFound();
});

it('returns 404 for a malformed locale', function () {
    $this->get('/RU-ru-ru')->assertNotFound();
});

it('ignores an unsupported locale stored on the user', function () {
    // A broken value in the profile isn't picked, but it doesn't fail
    // the request either: that source is just skipped, and the locale
    // comes from the next one.
    $user = User::factory()->create(['locale' => 'de']);

    $this->actingAs($user)->get(route('dash.home'));

    expect(app()->getLocale())->toBeIn(Locales::SUPPORTED)
        ->and(app()->getLocale())->not->toBe('de');
});

// Empty data
it('redirects the bare root to a supported locale', function () {
    // Laravel's test client sends Accept-Language en-us by default,
    // so the exact locale here depends on the client. What matters
    // is that the root never ends up with no locale at all.
    $response = $this->get('/');

    $response->assertRedirect();

    $target = ltrim(parse_url($response->headers->get('Location'), PHP_URL_PATH), '/');

    expect($target)->toBeIn(Locales::SUPPORTED);
});

it('falls back to ru when the browser sends no language', function () {
    $this->withHeaders(['Accept-Language' => ''])
        ->get('/')
        ->assertRedirect('/ru');
});

it('falls back to ru when the browser asks for a language we lack', function () {
    $this->withHeaders(['Accept-Language' => 'de-DE,de;q=0.9'])
        ->get('/')
        ->assertRedirect('/ru');
});

it('honours a browser language we do support', function () {
    $this->withHeaders(['Accept-Language' => 'kk-KZ,kk;q=0.9'])
        ->get('/')
        ->assertRedirect('/kk');
});

// A missing key is printed on screen as itself — `users.fields.email`
// where a label belongs. Nothing errors and nothing logs, so the only
// way to find it is to open every page in every language, or this.
//
// It used to compare `common.php` alone, while AGENTS.md said a test
// compares the key sets. That was true of one file out of eight: the
// per-entity dictionaries, which is where new copy actually lands, were
// never looked at. The list is built by globbing `lang/ru`, so a file
// added tomorrow is covered without anybody remembering to add it.
it('keeps every locale file in sync', function () {
    $reference = collect(File::glob(lang_path('ru/*.php')))
        ->merge(File::glob(lang_path('ru/entities/*.php')))
        ->map(fn (string $path) => str_replace(lang_path('ru').'/', '', $path));

    $drift = [];

    foreach ($reference as $file) {
        $ru = array_keys(Arr::dot(require lang_path("ru/{$file}")));

        foreach (['kk', 'en'] as $locale) {
            if (! File::exists(lang_path("{$locale}/{$file}"))) {
                $drift[] = "{$locale}/{$file}: missing entirely";

                continue;
            }

            $other = array_keys(Arr::dot(require lang_path("{$locale}/{$file}")));

            foreach (array_diff($ru, $other) as $key) {
                $drift[] = "{$locale}/{$file}: missing {$key}";
            }

            foreach (array_diff($other, $ru) as $key) {
                $drift[] = "{$locale}/{$file}: has {$key}, ru does not";
            }
        }
    }

    expect($drift)->toBe([]);
});

it('has more than one file to compare', function () {
    // A glob that matches nothing passes the assertion above. There are
    // eight: four shared dictionaries and one per entity.
    $files = collect(File::glob(lang_path('ru/*.php')))
        ->merge(File::glob(lang_path('ru/entities/*.php')));

    expect($files->count())->toBeGreaterThan(5);
});

// The locale cookie: pages outside the prefix — login and registration
// — otherwise always open in the fallback language, whatever the user
// actually chose.
it('remembers the locale chosen through the url', function () {
    $this->get('/kk')->assertCookie(Locales::COOKIE, 'kk', encrypted: false);
});

it('uses the remembered locale on pages outside the prefix', function () {
    $this->withUnencryptedCookie(Locales::COOKIE, 'kk')
        ->get(route('login'))
        ->assertInertia(fn ($page) => $page->where('locale', 'kk'));
});

it('lets the profile win over the cookie', function () {
    $user = User::factory()->create(['locale' => 'en']);

    $this->actingAs($user)
        ->withUnencryptedCookie(Locales::COOKIE, 'kk')
        ->get(route('profile.edit'))
        ->assertInertia(fn ($page) => $page->where('locale', 'en'));
});

it('ignores a broken cookie value', function () {
    $this->withUnencryptedCookie(Locales::COOKIE, 'de')
        ->get(route('login'))
        ->assertInertia(fn ($page) => $page->where('locale', fn ($l) => in_array($l, ['ru', 'kk', 'en'], true)));
});
