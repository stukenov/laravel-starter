<?php

namespace App\Http\Requests\Doc;

use App\Models\Doc;
use Illuminate\Foundation\Http\FormRequest;
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
            'title' => ['required', 'string', 'max:255'],
            'attachment' => ['nullable', 'file', 'max:'.config('files.max_kilobytes')],
        ];
    }

    /**
     * Route model binding is typed object|string as far as the analyser is
     * concerned. Narrow it once here instead of casting at every use.
     */
    private function target(): Doc
    {
        $record = $this->route('doc');

        if (! $record instanceof Doc) {
            throw new RuntimeException('The doc route parameter is not bound to a model.');
        }

        return $record;
    }
}
