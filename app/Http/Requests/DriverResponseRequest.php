<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DriverResponseRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || ! $user->role) {
            return false;
        }

        return in_array($user->role->name, ['SUPERADMIN', 'ADMIN', 'DRIVER']);
    }

    public function rules(): array
    {
        return [
            'action' => 'required|in:accept,reject',
            'reason' => 'required_if:action,reject|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'action.required' => 'Aksi wajib dipilih.',
            'reason.required_if' => 'Alasan penolakan wajib diisi.',
        ];
    }
}
