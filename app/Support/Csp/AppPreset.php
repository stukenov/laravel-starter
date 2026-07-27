<?php

namespace App\Support\Csp;

use Spatie\Csp\Directive;
use Spatie\Csp\Keyword;
use Spatie\Csp\Policy;
use Spatie\Csp\Preset;

/**
 * ПРАВИЛО(внешнее): ни одного чужого домена при отрисовке — AGENTS.md.
 *
 * Это не документация, а исполняемый запрет: браузер сам отклонит запрос
 * к постороннему хосту. Если однажды кто-то добавит виджет аналитики или
 * шрифт с CDN, страница сломается сразу и заметно, а не тихо утечёт.
 *
 * Отсюда следует, что reCAPTCHA и подобные сервисы в проекте невозможны
 * в принципе — вместо них honeypot.
 */
class AppPreset implements Preset
{
    public function configure(Policy $policy): void
    {
        $policy
            ->add(Directive::DEFAULT, Keyword::SELF)
            ->add(Directive::BASE, Keyword::SELF)
            ->add(Directive::CONNECT, Keyword::SELF)
            ->add(Directive::FORM_ACTION, Keyword::SELF)
            ->add(Directive::FRAME_ANCESTORS, Keyword::NONE)
            ->add(Directive::OBJECT, Keyword::NONE)
            // data: нужен для встроенных изображений и шрифтов, которые
            // собирает Vite. Это не внешний ресурс — данные лежат в самом
            // документе.
            ->add(Directive::IMG, [Keyword::SELF, 'data:'])
            ->add(Directive::FONT, [Keyword::SELF, 'data:'])
            // Стили инлайновые по необходимости: Laravel отдаёт шрифт
            // встроенным блоком, а Tailwind в разработке внедряет стили
            // в документ. Для скриптов такое послабление недопустимо —
            // там nonce.
            ->add(Directive::STYLE, [Keyword::SELF, Keyword::UNSAFE_INLINE])
            // 'self' обязателен рядом с nonce, и это не очевидно.
            // Один только nonce блокирует собственные бандлы Vite: у их
            // тегов <script src> nonce нет. Страница при этом отдаётся
            // со статусом 200 и с разметкой — ломается лишь весь
            // JavaScript, и увидеть это можно только в браузере.
            ->add(Directive::SCRIPT, Keyword::SELF)
            ->addNonce(Directive::SCRIPT);
    }
}
