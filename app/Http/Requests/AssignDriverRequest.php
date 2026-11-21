<?php

namespace App\Http\Requests;

use App\Models\Driver;
use App\Models\Vehicle;
use Illuminate\Foundation\Http\FormRequest;

class AssignDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || ! $user->role) {
            return false;
        }

        return in_array($user->role->name, ['SUPERADMIN', 'ADMIN', 'DISPATCHER']);
    }

    public function rules(): array
    {
        return [
            'driver_id' => 'required|exists:drivers,id',
            'vehicle_id' => 'required|exists:vehicles,id',
            // Driver pendukung loading (multi-driver, khusus trip bantuan loading).
            'additional_driver_ids' => 'nullable|array|max:10',
            'additional_driver_ids.*' => 'integer|distinct|exists:drivers,id',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $driver = Driver::find($this->input('driver_id'));
            $vehicle = Vehicle::find($this->input('vehicle_id'));

            if ($driver && $vehicle) {
                if (! $driver->allowedVehicles()->whereKey($vehicle->id)->exists()) {
                    $validator->errors()->add(
                        'vehicle_id',
                        'Driver tidak diizinkan menggunakan kendaraan yang dipilih.'
                    );
                }
            }

            $trip = $this->route('trip');
            $additional = $this->input('additional_driver_ids', []);

            // Multi-driver hanya untuk trip dengan kebutuhan bantuan loading.
            if (! empty($additional) && $trip && ! $trip->requires_loading_assistance) {
                $validator->errors()->add(
                    'additional_driver_ids',
                    'Penugasan driver tambahan hanya untuk trip yang membutuhkan bantuan loading.'
                );
            }

            // Driver tambahan harus bersedia membantu loading.
            if (! empty($additional)) {
                $unwilling = Driver::whereIn('id', $additional)->where('willing_to_load', false)->pluck('id');

                if ($unwilling->isNotEmpty()) {
                    $validator->errors()->add(
                        'additional_driver_ids',
                        'Semua driver pendukung loading harus bersedia membantu loading.'
                    );
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'driver_id.required' => 'Driver wajib dipilih.',
            'driver_id.exists' => 'Driver yang dipilih tidak valid.',
            'vehicle_id.required' => 'Kendaraan wajib dipilih.',
            'vehicle_id.exists' => 'Kendaraan yang dipilih tidak valid.',
            'additional_driver_ids.*.exists' => 'Driver tambahan yang dipilih tidak valid.',
            'additional_driver_ids.*.distinct' => 'Driver tambahan tidak boleh dipilih dua kali.',
        ];
    }
}
