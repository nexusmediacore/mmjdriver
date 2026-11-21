<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ApproveTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || ! $user->role) {
            return false;
        }

        $role = $user->role->name;

        // Approvers can approve trips from their department
        // Admins and SuperAdmins can approve all trips
        return in_array($role, ['SUPERADMIN', 'ADMIN', 'APPROVER']);
    }

    public function rules(): array
    {
        $rules = [
            'notes' => 'nullable|string',
        ];

        // Hanya SuperAdmin yang berwenang menetapkan prioritas final
        if ($this->user()?->role?->name === 'SUPERADMIN') {
            $rules['priority'] = 'required|in:NORMAL,URGENT,VERY_URGENT';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'priority.required' => 'Prioritas wajib ditetapkan oleh SuperAdmin.',
            'notes.max' => 'Catatan terlalu panjang.',
        ];
    }
}
