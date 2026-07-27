<?php

namespace App\Http\Requests\User;

use App\Http\Middleware\SetLocale;
use App\Models\User;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('user'));
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique(User::class)->ignore($this->route('user')->id),
            ],
            // Password is optional when editing: an empty field means
            // "keep the current one", not "wipe it".
            'password' => ['nullable', 'string', Password::defaults()],
            'locale' => ['required', 'string', Rule::in(SetLocale::SUPPORTED)],
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers())],
        ];
    }
}
