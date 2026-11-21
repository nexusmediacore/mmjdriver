<?php

namespace App\Http\Requests;

use App\Models\TripRequest;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Pengajuan selesai perjalanan (dual trigger: driver & employee pemohon).
 */
class SubmitTripCompletionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $trip = $this->route('trip');

        if (! $user || ! $user->role || ! $trip instanceof TripRequest) {
            return false;
        }

        if ($user->role->name === 'DRIVER') {
            return $trip->hasDriver($user);
        }

        return (int) $trip->requestor_id === (int) $user->id;
    }

    public function rules(): array
    {
        return [
            'notes' => 'nullable|string|max:2000',
        ];
    }

    public function messages(): array
    {
        return [
            'notes.max' => 'Catatan penyelesaian maksimal 2000 karakter.',
        ];
    }
}
