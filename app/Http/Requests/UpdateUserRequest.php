<?php

namespace App\Http\Requests;

use App\Models\User;
use App\Support\Role;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $this->user()?->can('update', $target instanceof User ? $target : new User) ?? false;
    }

    /**
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $target = $this->route('user');
        $userId = $target instanceof User ? $target->id : 0;

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'role' => ['required', Rule::in(Role::ALL)],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'phone' => ['nullable', 'string', 'max:30'],
            'nis' => ['nullable', 'string', 'max:30'],
            'nip' => ['nullable', 'string', 'max:30'],
            'title' => ['nullable', 'string', 'max:60'],
            // Password diubah hanya bila diisi; TIDAK ada endpoint reset password langsung.
            'password' => ['nullable', 'string', 'min:8', 'confirmed'],
            'send_reset_link' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama',
            'email' => 'email',
            'role' => 'peran',
            'status' => 'status',
            'password' => 'password baru',
        ];
    }
}
