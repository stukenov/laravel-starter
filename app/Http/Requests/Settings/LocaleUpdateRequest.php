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
            // The zone list comes from PHP itself rather than being typed
            // out, so it cannot go stale on the next tzdata update.
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers())],
            'locale' => ['required', 'string', Rule::in(SetLocale::SUPPORTED)],
        ];
    }
}
