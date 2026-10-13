<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGradeWeightsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        return $this->user()?->can('viewGradebook', $course) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'assignment' => ['required', 'numeric', 'min:0', 'max:100'],
            'quiz' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }

    /** Bobot kedua kategori wajib berjumlah tepat 100. */
    protected function prepareForValidation(): void
    {
        if ($this->has('assignment') && $this->has('quiz')) {
            $this->merge([
                'assignment' => (float) $this->input('assignment'),
                'quiz' => (float) $this->input('quiz'),
            ]);
        }
    }

    public function after(): array
    {
        return [
            function ($validator) {
                $total = (float) $this->input('assignment', 0) + (float) $this->input('quiz', 0);

                if (abs($total - 100) > 0.01) {
                    $validator->errors()->add(
                        'assignment',
                        'Total bobot Tugas dan Kuis harus tepat 100%. Saat ini '.round($total, 2).'%.'
                    );
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'assignment' => 'bobot tugas',
            'quiz' => 'bobot kuis',
        ];
    }
}
