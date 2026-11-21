<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Approval akhir (1x) penyelesaian perjalanan oleh Admin / SuperAdmin.
 */
class ApproveTripCompletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->role && in_array($user->role->name, ['SUPERADMIN', 'ADMIN'], true);
    }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:2000',
        ];
    }
}
