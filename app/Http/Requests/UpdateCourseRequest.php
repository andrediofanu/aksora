<?php

namespace App\Http\Requests;

use App\Models\Course;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        return $this->user()?->can('update', $course instanceof Course ? $course : new Course) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:2000'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'grade_level' => ['nullable', 'string', 'max:60'],
            'room' => ['nullable', 'string', 'max:60'],
            'max_members' => ['required', 'integer', 'min:1', 'max:500'],
            'allow_student_private_message' => ['nullable', 'boolean'],
            'status' => ['nullable', 'string', 'in:active,archived'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama kelas',
            'subject_id' => 'mata pelajaran',
            'academic_year_id' => 'tahun ajaran',
            'max_members' => 'kapasitas kelas',
        ];
    }
}
