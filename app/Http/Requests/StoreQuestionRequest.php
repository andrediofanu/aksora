<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $quiz = $this->route('quiz');

        return $this->user()?->can('update', $quiz) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $type = $this->input('type', 'multiple_choice');

        $rules = [
            'type' => ['required', Rule::in(['multiple_choice', 'short_answer'])],
            'question' => ['required', 'string', 'max:5000'],
            'points' => ['required', 'numeric', 'min:0.5', 'max:1000'],
            'correct_answer' => [$type === 'short_answer' ? 'required' : 'nullable', 'string', 'max:300'],
            'case_sensitive' => ['nullable', 'boolean'],
            'options' => [$type === 'multiple_choice' ? 'required' : 'nullable', 'array', 'min:2', 'max:8'],
            'options.*' => ['required', 'string', 'max:500'],
            'correct_option' => [$type === 'multiple_choice' ? 'required' : 'nullable', 'integer', 'min:0'],
        ];

        return $rules;
    }

    public function attributes(): array
    {
        return [
            'question' => 'pertanyaan',
            'points' => 'poin',
            'type' => 'jenis soal',
            'options' => 'pilihan jawaban',
            'correct_option' => 'kunci jawaban',
            'correct_answer' => 'kunci jawaban',
        ];
    }

    public function messages(): array
    {
        return [
            'options.min' => 'Pilihan jawaban minimal 2.',
            'options.required' => 'Pilihan jawaban wajib diisi untuk soal pilihan ganda.',
            'correct_option.required' => 'Tentukan kunci jawaban yang benar.',
            'correct_answer.required' => 'Kunci jawaban wajib diisi untuk soal jawaban singkat.',
        ];
    }
}
