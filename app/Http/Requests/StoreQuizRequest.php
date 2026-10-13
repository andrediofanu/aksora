<?php

namespace App\Http\Requests;

use App\Models\Quiz;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuizRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        return $this->user()?->can('create', [Quiz::class, $course]) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:5000'],
            'time_limit_minutes' => ['nullable', 'integer', 'min:1', 'max:600'],
            'opens_at' => ['nullable', 'date'],
            'closes_at' => ['nullable', 'date', 'after:opens_at'],
            'max_attempts' => ['required', 'integer', 'min:1', 'max:10'],
            'show_results' => ['required', Rule::in(['immediately', 'after_deadline', 'never'])],
            'pass_score' => ['required', 'numeric', 'min:0', 'max:100'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'status' => ['required', Rule::in(['draft', 'published'])],
            'published_at' => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'judul kuis',
            'time_limit_minutes' => 'batas waktu',
            'opens_at' => 'waktu buka',
            'closes_at' => 'waktu tutup',
            'max_attempts' => 'jumlah percobaan',
            'show_results' => 'penampilan hasil',
            'pass_score' => 'nilai kelulusan',
        ];
    }

    public function messages(): array
    {
        return [
            'closes_at.after' => 'Waktu tutup harus setelah waktu buka.',
        ];
    }
}
