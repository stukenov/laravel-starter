<?php

namespace App\Http\Requests\Role;

use App\Models\Role;
use App\Support\Access\Ability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use RuntimeException;

class UpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->target());
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'max:64', 'regex:/^[a-z][a-z0-9_-]*$/',
                Rule::unique('roles', 'name')->ignore($this->target()->id),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(Ability::all())],
        ];
    }

    private function target(): Role
    {
        $role = $this->route('role');

        if (! $role instanceof Role) {
            throw new RuntimeException('The role route parameter is not bound to a model.');
        }

        return $role;
    }
}
