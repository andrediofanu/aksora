<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $conversation = $this->route('conversation');

        return $this->user()?->can('send', $conversation) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'body' => ['required_without:file', 'nullable', 'string', 'max:5000'],
            'reply_to_id' => ['nullable', 'integer', 'exists:messages,id'],
            'file' => ['nullable', 'file', 'max:10240'],
        ];
    }

    public function attributes(): array
    {
        return [
            'body' => 'pesan',
            'reply_to_id' => 'pesan dibalas',
            'file' => 'lampiran',
        ];
    }

    public function messages(): array
    {
        return [
            'body.required_without' => 'Pesan tidak boleh kosong.',
            'body.max' => 'Pesan terlalu panjang (maksimal 5.000 karakter).',
        ];
    }
}
