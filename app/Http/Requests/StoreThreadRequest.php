<?php

namespace App\Http\Requests;

use App\Models\DiscussionThread;
use Illuminate\Foundation\Http\FormRequest;

class StoreThreadRequest extends FormRequest
{
    public function authorize(): bool
    {
        $course = $this->route('course');

        return $this->user()?->can('createThread', [DiscussionThread::class, $course]) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'body' => ['required', 'string', 'max:20000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'title' => 'judul diskusi',
            'body' => 'isi diskusi',
        ];
    }
}
