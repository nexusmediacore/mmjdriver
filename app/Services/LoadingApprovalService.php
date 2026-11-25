<?php

namespace App\Services;

use App\Models\Driver;
use App\Models\LoadingRequest;
use App\Models\TripAssignment;
use App\Models\TripRequest;
use App\Models\User;
use App\Notifications\LoadingApprovalDecision;
use App\Notifications\LoadingApprovalRequested;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Alur persetujuan Superadmin atas pemilihan driver bantu muat barang
 * pada trip Purchasing.
 *
 * Terpisah dari alur approval trip reguler agar tidak saling mengganggu.
 */
class LoadingApprovalService
{
    /**
     * Tandai trip sebagai menunggu persetujuan driver bantu muat.
     * Dipanggil saat trip Purchasing dengan driver bantu muat dibuat.
     */
    public function requestApproval(TripRequest $trip, ?string $note = null): TripRequest
    {
        DB::transaction(function () use ($trip, $note) {
            $trip->update([
                'loading_approval_status' => TripRequest::LOADING_PENDING,
                'loading_approval_note' => $note,
            ]);

            foreach (User::whereHas('role', fn ($q) => $q->where('name', 'SUPERADMIN'))->get() as $superadmin) {
                $superadmin->notify(new LoadingApprovalRequested($trip));
            }

            Log::info('Loading approval requested', ['trip_id' => $trip->id]);
        });

        return $trip->fresh();
    }

    public function approve(TripRequest $trip, User $superadmin, ?string $note = null): TripRequest
    {
        if ($trip->loading_approval_status !== TripRequest::LOADING_PENDING) {
            throw new \Exception('Persetujuan driver bantu muat tidak dapat diproses saat ini.');
        }

        return DB::transaction(function () use ($trip, $superadmin, $note) {
            $trip->update([
                'loading_approval_status' => TripRequest::LOADING_APPROVED,
                'loading_approved_by' => $superadmin->id,
                'loading_approved_at' => now(),
                'loading_approval_note' => $note ?? $trip->loading_approval_note,
                'updated_by' => $superadmin->id,
            ]);

            $this->notifyDecision($trip, true, $note);

            return $trip->fresh();
        });
    }

    public function reject(TripRequest $trip, User $superadmin, ?string $note = null): TripRequest
    {
        if ($trip->loading_approval_status !== TripRequest::LOADING_PENDING) {
            throw new \Exception('Persetujuan driver bantu muat tidak dapat diproses saat ini.');
        }

        return DB::transaction(function () use ($trip, $superadmin, $note) {
            $trip->update([
                'loading_approval_status' => TripRequest::LOADING_REJECTED,
                'loading_approved_by' => $superadmin->id,
                'loading_approved_at' => now(),
                'loading_approval_note' => $note ?? $trip->loading_approval_note,
                'updated_by' => $superadmin->id,
            ]);

            // Driver pendukung loading dilepas ketika ditolak.
            $trip->assignments()
                ->where('role', TripAssignment::ROLE_LOADING_SUPPORT)
                ->delete();

            $trip->loadingRequests()
                ->whereNotNull('driver_id')
                ->update(['approval_status' => LoadingRequest::APPROVAL_REJECTED]);

            $this->notifyDecision($trip, false, $note);

            return $trip->fresh();
        });
    }

    protected function notifyDecision(TripRequest $trip, bool $approved, ?string $note): void
    {
        $recipients = collect([$trip->requestor])->filter();

        $driverIds = $trip->assignments()
            ->where('role', TripAssignment::ROLE_LOADING_SUPPORT)
            ->pluck('driver_id');

        $driverUsers = User::whereIn('id', Driver::whereIn('id', $driverIds)->pluck('user_id'))->get();

        foreach ($recipients->concat($driverUsers)->unique('id') as $user) {
            $user->notify(new LoadingApprovalDecision($trip, $approved, $note));
        }
    }
}
