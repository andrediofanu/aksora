<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Pengumpulan tugas oleh murid.
 * Menangani teks dan/atau file sesuai `submission_type` tugas.
 */
class SubmitAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $assignment = $this->route('assignment');

        return $this->user()?->can('submit', $assignment) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $assignment = $this->route('assignment');
        $type = $assignment?->submission_type;

        $extensions = $assignment?->allowedExtensions() ?? [];

        return [
            'content' => [
                $type === 'file' ? 'nullable' : 'required',
                'string',
                'max:40000',
            ],
            'file' => [
                $type === 'text' ? 'nullable' : 'nullable',
                'file',
                'max:10240',
                'mimes:'.implode(',', $extensions),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'content' => 'jawaban teks',
            'file' => 'berkas',
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'Jawaban teks wajib diisi untuk tugas ini.',
            'file.mimes' => 'Jenis berkas tidak diizinkan untuk tugas ini.',
        ];
    }
}
