<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || ! $user->role) {
            return false;
        }

        return in_array($user->role->name, ['SUPERADMIN', 'ADMIN', 'APPROVER']);
    }

    public function rules(): array
    {
        return [
            'reason' => 'required|string|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'reason.required' => 'Alasan penolakan wajib diisi.',
        ];
    }
}
