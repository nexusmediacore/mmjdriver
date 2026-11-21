<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompleteTripRequest extends FormRequest
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
            'notes' => 'nullable|string',
        ];
    }
}
