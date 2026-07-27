<?php

/*
 * RULE(architecture): «великий монолит», а не слоёный пирог — AGENTS.md.
 *
 * Правило, записанное только текстом, живёт до первого спешащего
 * разработчика. Здесь оно исполняемое.
 */

arch()->preset()->laravel();
arch()->preset()->security();

/*
 * RULE(architecture): one monolith, not a layer cake — AGENTS.md.
 *
 * The ban is on the *habit*, not on the idea. Logic belongs in models,
 * form requests and policies, and a `UserService` wrapping four Eloquent
 * calls is a file that exists to be a file.
 *
 * Work that genuinely is not a model's — talking to another system,
 * generating a document, driving a subprocess — goes in `App\Support`
 * with the rest of the plumbing. That is where `Locale`, `Sessions`,
 * `Access` and `Sse` live. Porting a project with eight service classes
 * found this rule enforced and unexplained: it says where not to put
 * things and never says where to.
 */
arch('no repositories, and no service layer by that name')
    ->expect('App')
    ->not->toUse([
        'App\Repositories',
        'App\Services',
    ]);

// App\Actions is not banned: Fortify requires CreateNewUser and
// ResetUserPassword by contract. We do not add an action class per
// operation of our own — the logic lives in models, form requests and
// policies.
arch('only the action classes Fortify demands')
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
