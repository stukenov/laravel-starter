<?php

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;

// Correct data
it('gives users a uuid primary key defaulted by the database', function () {
    $column = DB::selectOne("
        select data_type, column_default from information_schema.columns
        where table_name = 'users' and column_name = 'id'
    ");

    expect($column->data_type)->toBe('uuid')
        ->and($column->column_default)->toContain('uuidv7()');
});

it('assigns a sortable id even on a raw insert bypassing Eloquent', function () {
    // Смысл значения по умолчанию на уровне базы: запись, вставленная
    // миграцией или скриптом, тоже получает корректный идентификатор.
    DB::insert('insert into users (name, email, password) values (?, ?, ?)', [
        'Тест', 'raw@example.test', 'x',
    ]);

    $id = DB::selectOne("select id from users where email = 'raw@example.test'")->id;

    expect($id)->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-/');
});

it('produces ids that sort by creation order', function () {
    // Главное свойство UUIDv7: вставка идёт в конец индекса, а не в середину.
    $first = User::factory()->create()->id;
    $second = User::factory()->create()->id;

    expect(strcmp($second, $first))->toBeGreaterThan(0);
});

it('stores email as citext', function () {
    $type = DB::selectOne("
        select udt_name from information_schema.columns
        where table_name = 'users' and column_name = 'email'
    ")->udt_name;

    expect($type)->toBe('citext');
});

it('finds a user regardless of email case', function () {
    User::factory()->create(['email' => 'Saken@Example.KZ']);

    expect(User::where('email', 'saken@example.kz')->exists())->toBeTrue();
});

it('has a generated search vector with a gin index', function () {
    $user = User::factory()->create(['name' => 'Сакен Тукенов']);

    $found = DB::selectOne(
        "select id from users where search_vector @@ plainto_tsquery('simple', ?)",
        ['Сакен'],
    );

    expect($found?->id)->toBe($user->id);
});

it('matches a partial name through pg_trgm', function () {
    $user = User::factory()->create(['name' => 'Тукенов']);

    $found = DB::selectOne('select id from users where name % ?', ['Тукен']);

    expect($found?->id)->toBe($user->id);
});

it('keeps the session user_id type in step with users.id', function () {
    // Расхождение типов ловится только при попытке связать таблицы,
    // то есть обычно уже в проде.
    $sessions = DB::selectOne("
        select data_type from information_schema.columns
        where table_name = 'sessions' and column_name = 'user_id'
    ")->data_type;

    expect($sessions)->toBe('uuid');
});

// Wrong data
it('treats emails differing only in case as duplicates', function () {
    User::factory()->create(['email' => 'Saken@Example.KZ']);

    expect(fn () => User::factory()->create(['email' => 'saken@example.kz']))
        ->toThrow(UniqueConstraintViolationException::class);
});

it('refuses a malformed uuid as a primary key', function () {
    expect(fn () => DB::insert(
        'insert into users (id, name, email, password) values (?, ?, ?, ?)',
        ['не-uuid', 'Тест', 'bad@example.test', 'x'],
    ))->toThrow(QueryException::class);
});

// Empty data
it('starts from an empty users table', function () {
    expect(User::count())->toBe(0);
});

it('returns nothing when searching an empty table', function () {
    $found = DB::select(
        "select id from users where search_vector @@ plainto_tsquery('simple', ?)",
        ['кто-нибудь'],
    );

    expect($found)->toBeEmpty();
});
