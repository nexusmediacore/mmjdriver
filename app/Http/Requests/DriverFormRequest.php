<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DriverFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->role && $user->role->name === 'SUPERADMIN';
    }

    public function rules(): array
    {
        return [
            'user_id' => 'required|exists:users,id',
            'license_number' => 'required|string|max:50',
            'vehicle_plate' => 'nullable|string|max:20',
            'vehicle_type' => 'nullable|string|max:50',
            'phone' => 'nullable|string|max:20',
            'status' => 'in:STANDBY,ASSIGNED,ACCEPTED,ON_TRIP,RETURNING,COMPLETED,OFF_DUTY,LEAVE,SICK,UNAVAILABLE',
            'willing_to_load' => 'sometimes|boolean',
            'notes' => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'User wajib dipilih.',
            'license_number.required' => 'Nomor SIM wajib diisi.',
        ];
    }
}
