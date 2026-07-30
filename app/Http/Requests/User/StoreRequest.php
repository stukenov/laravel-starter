<?php

declare(strict_types=1);

namespace App\Http\Requests\User;

use App\Models\User;
use App\Support\Locale\Locales;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', User::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            // Email is citext, so uniqueness is case-insensitive anyway —
            // but the rule must complain in plain words before the index
            // does it with an exception.
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'string', Password::defaults()],
            'locale' => ['required', 'string', Rule::in(Locales::SUPPORTED)],
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers())],
            // Only names that exist. A role invented through the
            // form would grant nothing and look like it granted something.
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ];
    }
}
