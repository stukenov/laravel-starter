<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * Две болезни, которые лечатся проверкой, а не внимательностью.
 *
 * 🔴 За сутки 05.08 я трижды подряд испортил файл ОДНИМ способом: якорь для
 * вставки выбирался по ТЕКСТУ СТРОКИ, а не по месту в структуре. Дважды вставка
 * встала между функцией и её docblock — комментарий с описанием сигнатуры
 * остался висеть над чужим объявлением. Один раз ключ перевода уехал не в тот
 * блок, потому что ключ с таким именем есть в двух блоках сразу.
 *
 * Оба раза находил не я: первый — PHPStan, ругаясь на СОСЕДНЮЮ функцию, второй —
 * печать ключей руками. Третьего раза могло не быть замечено вовсе.
 *
 * Здесь то же, что сделано со временем и `journal-entry.sh`: не «буду
 * внимательнее», а проверка, которая краснеет.
 *
 * 📌 Разбор — РОДНЫМ токенизатором PHP, а не регекспами. Первая версия обеих
 * проверок была на регекспах и дала 21 «задвоенный ключ», из которых настоящих
 * ноль: блоки с ЧИСЛОВЫМИ ключами (`403 => [`) регексп не считал вложенностью, и
 * внутренние `title` схлопывались в родительский. Число выглядело правдоподобно
 * и было артефактом инструмента.
 */

/** Все исходники, которые эти проверки обходят. */
function sourceFiles(): array
{
    $files = [];

    foreach ([app_path(), base_path('tests'), lang_path()] as $root) {
        foreach (File::allFiles($root) as $file) {
            if ($file->getExtension() === 'php' && ! str_contains($file->getPathname(), '/vendor/')) {
                $files[] = $file->getPathname();
            }
        }
    }

    return $files;
}

/**
 * Docblock с описанием СИГНАТУРЫ, под которым нет объявления.
 *
 * Такой комментарий описывает параметры, которых у стоящей ниже функции нет, —
 * то есть врёт молча. Именно эту форму оставляет вставка, попавшая между
 * функцией и её комментарием.
 *
 * @return list<string>
 */
function orphanSignatureDocs(string $path): array
{
    $tokens = token_get_all((string) file_get_contents($path));
    $orphans = [];

    foreach ($tokens as $i => $token) {
        if (! is_array($token) || $token[0] !== T_DOC_COMMENT) {
            continue;
        }

        if (! str_contains($token[1], '@param') && ! str_contains($token[1], '@return')) {
            continue;
        }

        // Что стоит СЛЕДОМ, пропуская пробелы и атрибуты.
        $next = null;

        for ($j = $i + 1; $j < count($tokens); $j++) {
            $candidate = $tokens[$j];

            if (is_array($candidate) && in_array($candidate[0], [T_WHITESPACE, T_ATTRIBUTE], true)) {
                continue;
            }

            $next = $candidate;
            break;
        }

        if ($next === null) {
            $orphans[] = $path.':'.$token[2];

            continue;
        }

        $kind = is_array($next) ? $next[0] : null;

        // Объявление — это функция, класс или модификатор перед ними.
        $declares = in_array($kind, [
            T_FUNCTION, T_CLASS, T_INTERFACE, T_TRAIT, T_ENUM,
            T_PUBLIC, T_PROTECTED, T_PRIVATE, T_STATIC, T_ABSTRACT, T_FINAL, T_READONLY,
            T_VAR, T_CONST,
        ], true);

        if (! $declares) {
            $orphans[] = $path.':'.$token[2];
        }
    }

    return $orphans;
}

/**
 * Ключи, объявленные В ОДНОМ И ТОМ ЖЕ массиве дважды.
 *
 * PHP оставляет ПОСЛЕДНИЙ и молчит: файл грузится, тест на «ключ есть»
 * проходит, а значение приходит не то. `array_keys()` дубль тоже не покажет —
 * он уже потерян к моменту чтения.
 *
 * @return list<string>
 */
function duplicateArrayKeys(string $path): array
{
    $tokens = array_values(array_filter(
        token_get_all((string) file_get_contents($path)),
        static fn ($t): bool => ! is_array($t) || ! in_array($t[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true),
    ));

    $scopes = [[]];
    // 🔴 Ветви `match` тоже пишутся через `=>`, но ключами массива НЕ являются.
    // Первая версия считала `'date', 'datetime' => ...` дублем ключа. Область
    // видимости у тела `match` своя и помечена: внутри неё ключи не собираются,
    // а массив, открытый внутри ветви, заводит свою обычную область.
    $isMatchScope = [false];
    $matchPending = false;
    $duplicates = [];

    foreach ($tokens as $i => $token) {
        // 🔴 Скобки считаются ОБЕ, и круглая тоже. Первая версия снимала
        // область видимости на `)`, но не заводила её на `(` — и любой вызов
        // функции схлопывал вложенность, сливая ключи в родительский массив.
        // Она дала 36 «дублей», из которых настоящих оказалось меньше трети:
        // `verify_sent` в трёх локалях был прочитан глазами и там ОДИН.
        $isOpen = $token === '[' || $token === '(' || (is_array($token) && $token[0] === T_ARRAY);
        $isClose = $token === ']' || $token === ')';

        if (is_array($token) && $token[0] === T_MATCH) {
            $matchPending = true;

            continue;
        }

        if ($token === '{') {
            $scopes[] = [];
            $isMatchScope[] = $matchPending;
            $matchPending = false;

            continue;
        }

        if ($token === '}' && count($scopes) > 1) {
            array_pop($scopes);
            array_pop($isMatchScope);

            continue;
        }

        if (is_array($token) && in_array($token[0], [T_CONSTANT_ENCAPSED_STRING, T_LNUMBER], true)) {
            $next = $tokens[$i + 1] ?? null;

            if (is_array($next) && $next[0] === T_DOUBLE_ARROW && ! $isMatchScope[count($isMatchScope) - 1]) {
                $key = trim($token[1], "'\"");

                if (in_array($key, $scopes[count($scopes) - 1], true)) {
                    $duplicates[] = $path.':'.$token[2].' — ключ '.$key;
                }

                $scopes[count($scopes) - 1][] = $key;
            }

            continue;
        }

        if ($isOpen) {
            $scopes[] = [];
            $isMatchScope[] = false;

            continue;
        }

        if ($isClose && count($scopes) > 1) {
            array_pop($scopes);
            array_pop($isMatchScope);
        }
    }

    return $duplicates;
}

// ────────────────────────────── правильные данные ──────────────────────────────

test('ни один docblock с описанием сигнатуры не остался без функции', function (): void {
    $orphans = [];

    foreach (sourceFiles() as $path) {
        $orphans = [...$orphans, ...orphanSignatureDocs($path)];
    }

    expect($orphans)->toBe([], "Комментарий описывает параметры функции, а под ним функции НЕТ.\n"
        .'Так выглядит вставка, попавшая между функцией и её docblock: '.PHP_EOL
        .implode(PHP_EOL, $orphans));
});

test('ни один ключ не объявлен в своём массиве дважды', function (): void {
    $duplicates = [];

    foreach (sourceFiles() as $path) {
        $duplicates = [...$duplicates, ...duplicateArrayKeys($path)];
    }

    expect($duplicates)->toBe([], "PHP оставит ПОСЛЕДНИЙ и промолчит.\n"
        .implode(PHP_EOL, $duplicates));
});

// ─────────────────────── положительный контроль обхода ───────────────────────

test('обход вообще читает файлы, а не пустоту', function (): void {
    // 🔴 Без этого «нарушений нет» неотличимо от «не прочитано ни одного файла»:
    // ровно та ошибка, из-за которой прежние разовые проверки честно проходили,
    // ничего не проверяя.
    //
    // 📌 Порог НЕ привязан к размеру одного проекта. Первая редакция требовала
    // «> 200 файлов» — числа панели, — и та же проверка, перенесённая в стартер
    // (183 файла), покраснела на пустом месте. Проверяется то, что нужно на
    // самом деле: КАЖДЫЙ корень дал файлы, и разбор нашёл комментарии.
    $files = sourceFiles();

    foreach ([app_path(), base_path('tests'), lang_path()] as $root) {
        $fromRoot = array_filter($files, static fn (string $p): bool => str_starts_with($p, $root));

        expect($fromRoot)->not->toBeEmpty("корень не дал ни одного файла: {$root}");
    }

    expect(count($files))->toBeGreaterThan(50);

    $docblocks = 0;

    foreach ($files as $path) {
        foreach (token_get_all((string) file_get_contents($path)) as $token) {
            if (is_array($token) && $token[0] === T_DOC_COMMENT) {
                $docblocks++;
            }
        }
    }

    expect($docblocks)->toBeGreaterThan(50);
});

// ─────────────────────────── проверка на опровержение ───────────────────────────

test('осиротевший docblock БЫЛ БЫ замечен', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'shape').'.php';

    file_put_contents($path, <<<'PHP'
    <?php

    class Probe
    {
        /**
         * @param  int  $gone
         */
        /**
         * Своя проза.
         */
        public function keep(string $kept): void {}
    }
    PHP);

    expect(orphanSignatureDocs($path))->toHaveCount(1);

    unlink($path);
});

test('задвоенный ключ БЫЛ БЫ замечен, в том числе внутри числового блока', function (): void {
    $path = tempnam(sys_get_temp_dir(), 'shape').'.php';

    // 🔴 Числовой блок здесь НАРОЧНО: первая версия проверки была на регекспе,
    // не считала `403 => [` вложенностью и объявляла дублями соседние `title`.
    file_put_contents($path, <<<'PHP'
    <?php

    return [
        'errors' => [
            403 => ['title' => 'нельзя'],
            404 => ['title' => 'нет'],
        ],
        'counts' => [
            'starved' => 'первое',
            'starved' => 'второе',
        ],
    ];
    PHP);

    $found = duplicateArrayKeys($path);

    expect($found)->toHaveCount(1)
        ->and($found[0])->toContain('starved');

    unlink($path);
});
