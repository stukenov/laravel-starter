<?php

namespace App\Http\Requests\Doc;

use App\Models\Doc;
use Illuminate\Foundation\Http\FormRequest;

class StoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', Doc::class);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'max:'.config('files.max_kilobytes')],
        ];
    }
}
