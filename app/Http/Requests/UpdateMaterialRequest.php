<?php

namespace App\Http\Requests;

use App\Models\LearningMaterial;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMaterialRequest extends FormRequest
{
    public function authorize(): bool
    {
        $material = $this->route('material');

        return $this->user()?->can('update', $material instanceof LearningMaterial ? $material : new LearningMaterial) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'topic' => ['nullable', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:2000'],
            'content' => ['nullable', 'string', 'max:65000'],
            'external_url' => ['nullable', 'url', 'max:500'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'status' => ['required', Rule::in(['draft', 'published', 'scheduled'])],
            'published_at' => ['required_if:status,scheduled', 'nullable', 'date'],
            'file' => ['nullable', 'file', 'max:10240'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'judul materi',
            'status' => 'status publikasi',
            'published_at' => 'jadwal terbit',
            'external_url' => 'tautan eksternal',
        ];
    }
}
