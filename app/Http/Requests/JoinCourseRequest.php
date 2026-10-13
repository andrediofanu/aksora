<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JoinCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:12'],
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'kode kelas',
        ];
    }

    public function messages(): array
    {
        return [
            'code.max' => 'Kode kelas tidak valid.',
        ];
    }
}
