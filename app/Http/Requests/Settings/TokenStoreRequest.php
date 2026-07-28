<?php

namespace App\Http\Requests\Settings;

use Illuminate\Foundation\Http\FormRequest;

class TokenStoreRequest extends FormRequest
{
    /**
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            // A name is the only way to tell one token from another when
            // the time comes to revoke one, so it is required.
            'name' => ['required', 'string', 'max:60'],
        ];
    }
}
