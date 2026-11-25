<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $trip = $this->route('trip');

        if (! $user || ! $user->role || ! $trip) {
            return false;
        }

        $role = $user->role->name;

        if (in_array($role, ['SUPERADMIN', 'ADMIN'], true)) {
            return true;
        }

        if ($role === 'EMPLOYEE' && $trip->requestor_id === $user->id) {
            return in_array($trip->status, ['DRAFT', 'SUBMITTED'], true);
        }

        return false;
    }

    public function rules(): array
    {
        $role = $this->user()?->role?->name;

        $rules = [
            'request_type' => 'sometimes|required|in:BUSINESS_TRIP,GUEST_PICKUP,CARGO_PICKUP',
            'destination' => 'sometimes|required|string|max:255',
            'destination_address' => 'nullable|string',
            'guest_pickup_location' => 'nullable|string|max:255',
            'guest_institution' => 'nullable|string|max:255',
            'cargo_pickup_location' => 'nullable|string|max:255',
            'cargo_description' => 'nullable|string|max:500',
            'bod_link' => ['nullable', 'string', 'max:2048', 'url:http,https'],
            'purpose' => 'sometimes|required|string',
            // Edit tidak memakai after_or_equal:now: jadwal lama yang
            // dikoreksi (typo jam/tanggal) tetap harus bisa disimpan.
            'departure_time' => 'sometimes|nullable|date',
            'estimated_return_time' => 'nullable|date|after:departure_time',
            'trip_date' => 'nullable|date',
            'passenger_count' => 'sometimes|required|integer|min:1|max:50',
            'requires_loading_assistance' => 'sometimes|boolean',
            'loading_support_driver_ids' => 'nullable|array|max:10',
            'loading_support_driver_ids.*' => 'integer|distinct|exists:drivers,id',
            'notes' => 'nullable|string',
            'passengers' => 'nullable|array',
            'passengers.*.name' => 'nullable|string|max:255',
            'passengers.*.phone' => 'nullable|string|max:20',
            'passengers.*.department' => 'nullable|string|max:255',
            'passengers.*.position' => 'nullable|string|max:255',
        ];

        // Mode muat barang (CARGO_PICKUP): tanpa informasi perjalanan —
        // hanya info barang. Field perjalanan dibuat opsional agar edit
        // tidak ditolak dengan "Keperluan wajib diisi" (form muat barang
        // tidak memiliki kolom Keperluan).
        if ($this->input('request_type') === 'CARGO_PICKUP') {
            // Informasi barang yang wajib pada formulir muat barang.
            $rules['cargo_pickup_location'] = 'required|string|max:255';
            $rules['cargo_description'] = 'required|string|max:500';
            // Informasi perjalanan tidak tersedia di formulir muat barang.
            $rules['destination'] = 'nullable|string|max:255';
            $rules['purpose'] = 'nullable|string';
            $rules['passenger_count'] = 'nullable|integer|min:1|max:50';
            $rules['departure_time'] = 'nullable|date';
            $rules['guest_pickup_location'] = 'nullable|string|max:255';
            $rules['guest_institution'] = 'nullable|string|max:255';
        }

        if ($role === 'ADMIN') {
            $rules['priority'] = 'nullable|in:NORMAL';
            $rules['requested_priority'] = 'nullable|in:NORMAL,URGENT,VERY_URGENT';
            $rules['urgency_notes'] = 'nullable|string|required_if:requested_priority,URGENT,VERY_URGENT';
        } else {
            $rules['priority'] = 'nullable|in:NORMAL,URGENT,VERY_URGENT';
        }

        if ($role === 'SUPERADMIN') {
            $rules['department_id'] = 'nullable|integer|exists:departments,id';
            // Trip BOD: driver utama dapat diganti langsung di form edit.
            $rules['driver_id'] = 'nullable|integer|exists:drivers,id';
        } else {
            $rules['department_id'] = 'prohibited';
            $rules['driver_id'] = 'prohibited';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'request_type.in' => 'Jenis permintaan tidak valid.',
            'destination.required' => 'Tujuan wajib diisi.',
            'purpose.required' => 'Keperluan wajib diisi.',
            'cargo_pickup_location.required' => 'Lokasi muat barang wajib diisi.',
            'cargo_description.required' => 'Deskripsi barang wajib diisi.',
            'departure_time.required' => 'Jam berangkat wajib diisi.',
            'departure_time.after_or_equal' => 'Jam berangkat tidak boleh di masa lalu.',
            'passenger_count.required' => 'Jumlah penumpang wajib diisi.',
            'passenger_count.integer' => 'Jumlah penumpang harus berupa angka.',
            'passenger_count.min' => 'Jumlah penumpang minimal 1.',
            'passenger_count.max' => 'Jumlah penumpang maksimal 50.',
            'department_id.exists' => 'Departemen yang dipilih tidak valid.',
            'bod_link.url' => 'Tautan harus berupa URL valid dengan awalan http:// atau https://.',
        ];
    }
}
