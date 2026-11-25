<?php

namespace App\Policies;

use App\Models\Department;
use App\Models\TripRequest;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class TripRequestPolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user): bool
    {
        return $user->role && in_array($user->role->name, ['SUPERADMIN', 'ADMIN', 'DISPATCHER', 'APPROVER', 'EMPLOYEE', 'DRIVER']);
    }

    public function view(User $user, TripRequest $tripRequest): bool
    {
        $role = $user->role->name ?? null;

        // SuperAdmin and Admin can view all
        if (in_array($role, ['SUPERADMIN', 'ADMIN'])) {
            return true;
        }

        // Dispatcher can view all trips
        if ($role === 'DISPATCHER') {
            return true;
        }

        // Approver can view trips from their department
        if ($role === 'APPROVER' && $tripRequest->department_id === $user->department_id) {
            return true;
        }

        // Employee can only view their own trips
        if ($role === 'EMPLOYEE' && $tripRequest->requestor_id === $user->id) {
            return true;
        }

        // Driver can view trips assigned to them (driver utama maupun pendukung loading)
        if ($role === 'DRIVER') {
            return $tripRequest->hasDriver($user);
        }

        return false;
    }

    public function create(User $user, ?array $data = null): bool
    {
        $role = $user->role?->name;

        if (! $role) {
            return false;
        }

        // Driver hanya boleh membuat Trip Request khusus BOD: policy
        // menerima data request dan memeriksa departemen tujuan.
        if ($role === 'DRIVER') {
            $departmentId = $data['department_id'] ?? $user->department_id;

            return Department::isBod((int) $departmentId);
        }

        return in_array($role, ['SUPERADMIN', 'ADMIN', 'EMPLOYEE']);
    }

    public function update(User $user, TripRequest $tripRequest): bool
    {
        $role = $user->role->name ?? null;

        if (in_array($role, ['SUPERADMIN', 'ADMIN'])) {
            return true;
        }

        // Employee can update only if trip is in DRAFT or SUBMITTED
        if ($role === 'EMPLOYEE' && $tripRequest->requestor_id === $user->id) {
            return in_array($tripRequest->status, ['DRAFT', 'SUBMITTED']);
        }

        return false;
    }

    public function delete(User $user, TripRequest $tripRequest): bool
    {
        $role = $user->role->name ?? null;

        if (in_array($role, ['SUPERADMIN', 'ADMIN'])) {
            return true;
        }

        // Employee can delete only their own DRAFT trips
        if ($role === 'EMPLOYEE' && $tripRequest->requestor_id === $user->id) {
            return $tripRequest->status === 'DRAFT';
        }

        return false;
    }

    public function submit(User $user, TripRequest $tripRequest): bool
    {
        $role = $user->role->name ?? null;

        if ($role === 'EMPLOYEE' && $tripRequest->requestor_id === $user->id) {
            return $tripRequest->status === 'DRAFT';
        }

        return false;
    }

    public function approve(User $user, TripRequest $tripRequest): bool
    {
        $role = $user->role->name ?? null;

        if (in_array($role, ['SUPERADMIN', 'ADMIN'])) {
            return true;
        }

        if ($role === 'APPROVER' && $tripRequest->department_id === $user->department_id) {
            return $tripRequest->status === 'WAITING_APPROVAL';
        }

        return false;
    }

    public function assignDriver(User $user, TripRequest $tripRequest): bool
    {
        $role = $user->role->name ?? null;

        return in_array($role, ['SUPERADMIN', 'ADMIN', 'DISPATCHER']);
    }

    /**
     * Pengajuan selesai perjalanan: driver yang ditugaskan atau pemohon,
     * hanya saat trip sedang berlangsung (ON_TRIP).
     */
    public function submitCompletion(User $user, TripRequest $tripRequest): bool
    {
        if ($tripRequest->status !== TripRequest::STATUS_ON_TRIP) {
            return false;
        }

        $role = $user->role->name ?? null;

        if ($role === 'DRIVER') {
            return $tripRequest->hasDriver($user);
        }

        return (int) $tripRequest->requestor_id === (int) $user->id;
    }

    /**
     * Penyelesaian perjalanan kini langsung oleh driver/pemohon tanpa
     * approval Admin/SuperAdmin — approveCompletion dihapus.
     */
}
