<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehicleFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->role && $user->role->name === 'SUPERADMIN';
    }

    public function rules(): array
    {
        $rules = [
            'plate_number' => 'required|string|max:20|unique:vehicles,plate_number',
            'brand' => 'required|string|max:50',
            'model' => 'nullable|string|max:50',
            'type' => 'nullable|string|max:50',
            'year' => 'nullable|integer|min:1900|max:'.(date('Y') + 1),
            'passenger_capacity' => 'nullable|integer|min:1|max:100',
            'status' => 'in:AVAILABLE,ASSIGNED,ON_TRIP,MAINTENANCE,OUT_OF_SERVICE',
            'primary_driver_id' => 'nullable|exists:drivers,id',
            'notes' => 'nullable|string',
            'last_service_date' => 'nullable|date',
            'next_service_date' => 'nullable|date|after_or_equal:last_service_date',
        ];

        if ($this->route('vehicle')) {
            $rules['plate_number'] = 'required|string|max:20|unique:vehicles,plate_number,'.$this->route('vehicle')->id;
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'plate_number.required' => 'Plat nomor wajib diisi.',
            'brand.required' => 'Merk wajib diisi.',
        ];
    }
}
