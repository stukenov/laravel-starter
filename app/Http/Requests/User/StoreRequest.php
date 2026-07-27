<?php

namespace App\Http\Requests\User;

use App\Http\Middleware\SetLocale;
use App\Models\User;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Почта в citext: уникальность и так регистронезависимая,
            // но правило обязано ругаться понятным сообщением раньше,
            // чем сработает индекс.
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', Password::defaults()],
            'locale' => ['required', 'string', Rule::in(SetLocale::SUPPORTED)],
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers())],
        ];
    }
}
