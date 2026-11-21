<?php

namespace App\Http\Requests;

use App\Models\Department;
use App\Models\Driver;
use Illuminate\Foundation\Http\FormRequest;

class StoreTripRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->role && in_array($user->role->name, ['SUPERADMIN', 'ADMIN', 'EMPLOYEE']);
    }

    public function rules(): array
    {
        $rules = [
            'request_type' => 'required|in:BUSINESS_TRIP,GUEST_PICKUP,CARGO_PICKUP',
            'destination' => 'required|string|max:255',
            'destination_address' => 'nullable|string',
            'guest_pickup_location' => 'required_if:request_type,GUEST_PICKUP|nullable|string|max:255',
            'guest_institution' => 'required_if:request_type,GUEST_PICKUP|nullable|string|max:255',
            'cargo_pickup_location' => 'required_if:request_type,CARGO_PICKUP|nullable|string|max:255',
            'cargo_description' => 'required_if:request_type,CARGO_PICKUP|nullable|string|max:500',
            // Tautan referensi trip BOD (Google Maps / agenda / dokumen).
            // Hanya skema http/https agar aman dari XSS & skema berbahaya.
            'bod_link' => ['nullable', 'string', 'max:2048', 'url:http,https'],
            'purpose' => 'required|string',
            'departure_time' => 'required|date|after_or_equal:now',
            'estimated_return_time' => 'nullable|date|after:departure_time',
            'trip_date' => 'nullable|date',
            'passenger_count' => 'required|integer|min:1|max:50',
            'requires_loading_assistance' => 'sometimes|boolean',
            // Driver pendukung loading (bisa lebih dari satu) yang dipilih
            // langsung saat membuat trip request. Hanya driver STANDBY yang
            // bersedia membantu loading yang boleh dipilih.
            'loading_support_driver_ids' => 'nullable|array|max:10',
            'loading_support_driver_ids.*' => 'integer|distinct|exists:drivers,id',
            'notes' => 'nullable|string',
            'passengers' => 'nullable|array',
            'passengers.*.name' => 'required|string|max:255',
            'passengers.*.phone' => 'nullable|string|max:20',
            'passengers.*.department' => 'nullable|string|max:255',
            'passengers.*.position' => 'nullable|string|max:255',
        ];

        $role = $this->user()?->role?->name;

        // Trip BOD (Board of Directors) otomatis disetujui dan TIDAK perlu
        // mengisi informasi perjalanan — cukup departemen saja. Detail
        // perjalanan boleh dikosongkan dan diisi belakangan saat dispatch.
        if ($this->isBodRequest()) {
            $rules['destination'] = 'nullable|string|max:255';
            $rules['guest_pickup_location'] = 'nullable|string|max:255';
            $rules['guest_institution'] = 'nullable|string|max:255';
            $rules['bod_link'] = ['nullable', 'string', 'max:2048', 'url:http,https'];
            $rules['purpose'] = 'nullable|string';
            $rules['departure_time'] = 'nullable|date';
            $rules['estimated_return_time'] = 'nullable|date';
            $rules['passenger_count'] = 'nullable|integer|min:1|max:50';
            $rules['priority'] = 'nullable|in:NORMAL,URGENT,VERY_URGENT';
            $rules['requested_priority'] = 'nullable|in:NORMAL,URGENT,VERY_URGENT';
            $rules['urgency_notes'] = 'nullable|string';
            $rules['passengers'] = 'nullable|array';
            $rules['passengers.*.name'] = 'nullable|string|max:255';

            // BOD memilih driver langsung saat membuat trip.
            $rules['driver_id'] = 'required|integer|exists:drivers,id';
        }

        if ($role === 'ADMIN') {
            // Admin departemen: prioritas DITETAPKAN SuperAdmin.
            // Admin hanya dapat MENGUSULKAN prioritas beserta alasannya.
            $rules['priority'] = 'nullable|in:NORMAL';
            $rules['requested_priority'] = 'required|in:NORMAL,URGENT,VERY_URGENT';
            $rules['urgency_notes'] = 'required_if:requested_priority,URGENT,VERY_URGENT|nullable|string';
        } else {
            // SuperAdmin/Employee menentukan prioritas secara langsung
            $rules['priority'] = $rules['priority'] ?? 'required|in:NORMAL,URGENT,VERY_URGENT';
        }

        if ($role === 'SUPERADMIN') {
            $rules['department_id'] = 'required|integer|exists:departments,id';
        } else {
            $rules['department_id'] = 'prohibited';
            $rules['driver_id'] = 'prohibited';
        }

        return $rules;
    }

    /**
     * True bila request ini membuat trip untuk departemen BOD.
     */
    protected function isBodRequest(): bool
    {
        $departmentId = $this->input('department_id');

        if ($departmentId === null || $departmentId === '') {
            // Non-SuperAdmin membuat trip untuk departemennya sendiri.
            $departmentId = $this->user()?->department_id;
        }

        return Department::isBod((int) $departmentId);
    }

    /**
     * Validasi silang setelah aturan utama:
     * 1. Non-SuperAdmin wajib memiliki departemen.
     * 2. Pilihan driver pendukung loading hanya bila trip membutuhkan
     *    bantuan loading, dan seluruh driver harus berstatus STANDBY
     *    serta bersedia membantu loading.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $user = $this->user();

            if ($user && $user->role?->name !== 'SUPERADMIN' && ! $user->department_id) {
                $validator->errors()->add(
                    'department_id',
                    'Akun Anda belum memiliki departemen. Hubungi administrator untuk mengatur departemen pada akun ini.'
                );
            }

            $ids = collect($this->input('loading_support_driver_ids', []))
                ->filter()
                ->unique()
                ->values();

            if ($ids->isEmpty()) {
                return;
            }

            if (! $this->boolean('requires_loading_assistance')) {
                $validator->errors()->add(
                    'loading_support_driver_ids',
                    'Driver pendukung loading hanya untuk permintaan dengan bantuan loading.'
                );

                return;
            }

            $drivers = Driver::whereIn('id', $ids)->get();

            $notStandby = $drivers->filter(fn ($driver) => $driver->status !== Driver::STATUS_STANDBY);
            if ($notStandby->isNotEmpty()) {
                $validator->errors()->add(
                    'loading_support_driver_ids',
                    'Semua driver pendukung loading harus berstatus Standby.'
                );
            }

            $unwilling = $drivers->filter(fn ($driver) => ! $driver->willing_to_load);
            if ($unwilling->isNotEmpty()) {
                $validator->errors()->add(
                    'loading_support_driver_ids',
                    'Semua driver pendukung loading harus bersedia membantu loading.'
                );
            }
        });
    }

    public function messages(): array
    {
        return [
            'request_type.required' => 'Jenis permintaan wajib dipilih.',
            'request_type.in' => 'Jenis permintaan tidak valid.',
            'guest_pickup_location.required_if' => 'Lokasi penjemputan tamu wajib diisi.',
            'guest_institution.required_if' => 'Instansi/instalasi tamu wajib diisi.',
            'cargo_pickup_location.required_if' => 'Lokasi muat barang wajib diisi.',
            'cargo_description.required_if' => 'Deskripsi barang wajib diisi.',
            'priority.required' => 'Prioritas wajib dipilih.',
            'requested_priority.required' => 'Prioritas usulan wajib dipilih.',
            'urgency_notes.required_if' => 'Alasan urgensi wajib diisi bila mengusulkan prioritas Urgent / Very Urgent.',
            'destination.required' => 'Tujuan wajib diisi.',
            'purpose.required' => 'Keperluan wajib diisi.',
            'departure_time.required' => 'Jam berangkat wajib diisi.',
            'passenger_count.required' => 'Jumlah penumpang wajib diisi.',
            'department_id.required' => 'Departemen wajib dipilih.',
            'department_id.exists' => 'Departemen yang dipilih tidak valid.',
            'bod_link.url' => 'Tautan harus berupa URL valid dengan awalan http:// atau https://.',
            'bod_link.max' => 'Tautan maksimal 2048 karakter.',
        ];
    }
}
