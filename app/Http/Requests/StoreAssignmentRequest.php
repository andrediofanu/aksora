<?php

namespace App\Http\Requests;

use App\Models\Assignment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAssignmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        return $this->user()?->can('create', [Assignment::class, $course]) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'instructions' => ['nullable', 'string', 'max:20000'],
            'submission_type' => ['required', Rule::in(['text', 'file', 'both'])],
            'due_at' => ['nullable', 'date', 'after:now'],
            'allow_late' => ['nullable', 'boolean'],
            'max_score' => ['required', 'numeric', 'min:1', 'max:1000'],
            'weight' => ['required', 'numeric', 'min:0.01', 'max:100'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
            'allowed_extensions' => ['nullable', 'array', 'max:20'],
            'allowed_extensions.*' => ['string', 'max:10', 'regex:/^[a-zA-Z0-9]+$/'],
            'rubric' => ['nullable', 'string', 'max:20000'],
            'file' => ['nullable', 'file', 'max:10240'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'judul tugas',
            'submission_type' => 'jenis pengumpulan',
            'due_at' => 'tenggat waktu',
            'max_score' => 'nilai maksimum',
            'weight' => 'bobot poin',
            'allowed_extensions' => 'ekstensi file',
        ];
    }

    public function messages(): array
    {
        return [
            'due_at.after' => 'Tenggat waktu harus setelah waktu sekarang.',
        ];
    }
}
