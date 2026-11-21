<?php

namespace App\Http\Requests;

use App\Services\DriverAttendanceService;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Absensi driver (check-in / check-out) dengan foto selfie.
 *
 * Validasi file ketat: hanya image jpg/jpeg/png/webp, maksimal 5 MB.
 */
class DriverAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();

        return $user && $user->role && $user->role->name === 'DRIVER' && $user->driver !== null;
    }

    public function rules(): array
    {
        return [
            'photo' => [
                'required',
                'file',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'mimetypes:image/jpeg,image/png,image/webp',
                // max() untuk file menggunakan kilobytes: 5120 kb = 5 MB.
                'max:'.(int) (DriverAttendanceService::MAX_PHOTO_BYTES / 1024),
            ],
            'lat' => 'nullable|numeric|between:-90,90',
            'lng' => 'nullable|numeric|between:-180,180',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function messages(): array
    {
        return [
            'photo.required' => 'Foto selfie wajib diambil untuk absensi.',
            'photo.image' => 'Berkas absensi harus berupa gambar.',
            'photo.mimes' => 'Format foto harus JPG, JPEG, PNG, atau WEBP.',
            'photo.mimetypes' => 'Isi berkas foto tidak valid (bukan gambar JPG/PNG/WEBP).',
            'photo.max' => 'Ukuran foto maksimal 5 MB.',
        ];
    }
}
