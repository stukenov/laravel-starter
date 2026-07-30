<?php

declare(strict_types=1);

namespace App\Http\Requests\Role;

use App\Models\Role;
use App\Support\Access\Ability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Role::class) ?? false;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_-]*$/', Rule::unique('roles', 'name')],
            'permissions' => ['array'],
            // A permission not declared in code enforces nothing, so the
            // list is closed rather than free-form.
            'permissions.*' => ['string', Rule::in(Ability::all())],
        ];
    }
}
