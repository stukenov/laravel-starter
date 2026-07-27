<?php

namespace App\Http\Requests\User;

use App\Models\User;
use App\Support\Locale\Locales;
use DateTimeZone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use RuntimeException;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->target());
    }

    /**
     * Route model binding gives back object|string as far as the analyser
     * is concerned. Narrow it once, here, instead of casting at each use.
     */
    private function target(): User
    {
        $user = $this->route('user');

        if (! $user instanceof User) {
            throw new RuntimeException('The user route parameter is not bound to a model.');
        }

        return $user;
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
                Rule::unique(User::class)->ignore($this->target()->id),
            ],
            // Password is optional when editing: an empty field means
            // "keep the current one", not "wipe it".
            'password' => ['nullable', 'string', Password::defaults()],
            'locale' => ['required', 'string', Rule::in(Locales::SUPPORTED)],
            'timezone' => ['required', 'string', Rule::in(DateTimeZone::listIdentifiers())],
            // Only names that exist. A role invented through the
            // form would grant nothing and look like it granted something.
            'roles' => ['sometimes', 'array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ];
    }
}
