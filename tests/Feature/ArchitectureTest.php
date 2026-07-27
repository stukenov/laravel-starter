<?php

/*
 * ПРАВИЛО(архитектура): «великий монолит», а не слоёный пирог — AGENTS.md.
 *
 * Правило, записанное только текстом, живёт до первого спешащего
 * разработчика. Здесь оно исполняемое.
 */

arch()->preset()->laravel();
arch()->preset()->security();

arch('репозиториев и сервисного слоя у нас нет')
    ->expect('App')
    ->not->toUse([
        'App\Repositories',
        'App\Services',
    ]);

// App\Actions в запрет не входит: Fortify требует CreateNewUser
// и ResetUserPassword по контракту. Но своих классов-действий
// на каждую операцию мы не заводим — логика живёт в моделях,
// form requests и политиках.
arch('классы-действия только те, что требует Fortify')
    ->expect('App\Actions')
    ->toBeClasses()
    ->toOnlyBeUsedIn('App\Providers');

arch('модели живут в одном месте и наследуют Eloquent')
    ->expect('App\Models')
    ->toBeClasses()
    ->toExtend('Illuminate\Database\Eloquent\Model');

arch('политики называются единообразно')
    ->expect('App\Policies')
    ->toHaveSuffix('Policy');

arch('запросы формы называются единообразно')
    ->expect('App\Http\Requests')
    ->toHaveSuffix('Request');

arch('middleware называются единообразно')
    ->expect('App\Http\Middleware')
    ->toBeClasses();

arch('контроллеры не ходят в базу напрямую')
    ->expect('App\Http\Controllers')
    ->not->toUse('Illuminate\Support\Facades\DB');

arch('в коде не осталось отладочного мусора')
    ->expect(['dd', 'dump', 'ray', 'var_dump', 'print_r'])
    ->not->toBeUsed();
