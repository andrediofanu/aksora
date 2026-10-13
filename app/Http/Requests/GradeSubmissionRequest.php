<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GradeSubmissionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $submission = $this->route('submission');

        return $this->user()?->can('update', $submission) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $submission = $this->route('submission');
        $max = (float) ($submission?->assignment?->max_score ?? 1000);

        return [
            'score' => ['required', 'numeric', 'min:0', 'max:'.$max],
            'feedback' => ['nullable', 'string', 'max:5000'],
            'status' => ['nullable', 'in:graded,revision,returned'],
        ];
    }

    public function attributes(): array
    {
        return [
            'score' => 'nilai',
            'feedback' => 'umpan balik',
            'status' => 'status penilaian',
        ];
    }

    public function messages(): array
    {
        return [
            'score.max' => 'Nilai tidak boleh melebihi nilai maksimum tugas.',
        ];
    }
}
