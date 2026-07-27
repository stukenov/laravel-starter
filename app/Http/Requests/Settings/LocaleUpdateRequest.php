<?php

namespace App\Http\Requests\Settings;

use App\Http\Middleware\SetLocale;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LocaleUpdateRequest extends FormRequest
{
    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            // Список зон берётся у самого PHP, а не пишется руками:
            // так он не устареет при следующем обновлении tzdata.
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers())],
            'locale' => ['required', 'string', Rule::in(SetLocale::SUPPORTED)],
        ];
    }
}
