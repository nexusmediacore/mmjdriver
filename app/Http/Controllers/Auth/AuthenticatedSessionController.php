<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/Login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => session('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        // Hanya kembalikan pengguna ke halaman semula bila tujuan tersebut
        // aman dan tidak bergantung pada kepemilikan record. Ini mencegah
        // driver "terpental" kembali ke URL yang akan menghasilkan 403
        // setelah login.
        $safePaths = ['dashboard', 'driver/my-tasks', 'driver/trip-history', 'trips', 'reports'];

        $target = route('dashboard', absolute: false);

        $intended = $request->session()->pull('url.intended');
        if (is_string($intended)) {
            $path = ltrim((string) parse_url($intended, PHP_URL_PATH), '/');
            if ($path !== '' && in_array($path, $safePaths, true)) {
                $target = '/'.$path;
            }
        }

        return redirect()->to($target);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
