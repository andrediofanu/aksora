<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StorePostRequest extends FormRequest
{
    public function authorize(): bool
    {
        $thread = $this->route('thread');

        return $this->user()?->can('reply', $thread) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required', 'string', 'max:20000'],
            'parent_id' => ['nullable', 'integer', 'exists:discussion_posts,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'body' => 'balasan',
            'parent_id' => 'balasan induk',
        ];
    }
}
