<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'auth.user' => $request->user()
                ? $request->user()->load('role', 'department')
                : null,
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'info' => $request->session()->get('info'),
                'warning' => $request->session()->get('warning'),
                'whatsapp_link' => $request->session()->get('whatsapp_link'),
            ],
            'unreadNotifications' => $request->user()
                ? $request->user()->unreadNotifications()
                    ->limit(10)
                    ->get()
                    ->map(fn ($n) => [
                        'id' => $n->id,
                        'type' => $n->type,
                        'data' => $n->data,
                        'read_at' => $n->read_at,
                        'created_at' => $n->created_at->toIso8601String(),
                    ])
                    ->toArray()
                : [],
            'unreadNotificationsCount' => $request->user()
                ? $request->user()->unreadNotifications()->count()
                : 0,
        ];
    }
}
