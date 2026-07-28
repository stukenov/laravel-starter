<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->profileRules($this->user()->id) + [
            // Extension and detected type checked separately: a .png that
            // is really a script passes the first and fails the second.
            'avatar' => [
                'nullable',
                'image',
                'mimes:'.implode(',', config('files.image_mimes')),
                'max:'.config('files.max_kilobytes'),
            ],
        ];
    }
}
