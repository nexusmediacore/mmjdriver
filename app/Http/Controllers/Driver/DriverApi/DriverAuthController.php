<?php

namespace App\Http\Controllers\Driver\DriverApi;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class DriverAuthController extends Controller
{
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
            'device_name' => 'required|string',
        ]);

        $user = User::with('role', 'driver')->where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Email atau password tidak sesuai.'],
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => ['Akun Anda tidak aktif.'],
            ]);
        }

        if ($user->role && $user->role->name !== 'DRIVER') {
            throw ValidationException::withMessages([
                'email' => ['Akun ini bukan driver.'],
            ]);
        }

        $user->update(['last_login_at' => now()]);

        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'token' => $token,
            'user' => $user->only([
                'id',
                'name',
                'email',
                'phone',
                'role_id',
            ]) + [
                'role' => $user->role?->name,
                'driver' => $user->driver ? $user->driver->only(['id', 'status']) : null,
            ],
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load('role', 'driver');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'phone' => $user->phone,
                'role' => $user->role?->name,
                'driver' => $user->driver ? $user->driver->only(['id', 'status']) : null,
                'is_active' => $user->is_active,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json(['message' => 'Logout berhasil']);
    }

    public function refresh(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();
        $token = $request->user()->createToken('mobile-refresh')->plainTextToken;

        return response()->json([
            'token' => $token,
        ]);
    }

    public function registerDevice(Request $request): JsonResponse
    {
        $request->validate(['fcm_token' => 'nullable|string']);

        return response()->json(['message' => 'OK']);
    }
}
