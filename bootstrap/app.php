<?php

use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\NoCacheHeaders;
use App\Http\Middleware\RoleMiddleware;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Percayakan header proxy (ngrok/Cloudflare/load balancer) agar Laravel
        // tahu skema HTTPS asli. Tanpa ini, semua URL aset dibuat dengan
        // http:// sehingga diblokir browser saat halaman dibuka lewat HTTPS.
        $middleware->trustProxies(at: '*', headers: Request::HEADER_X_FORWARDED_FOR |
            Request::HEADER_X_FORWARDED_HOST |
            Request::HEADER_X_FORWARDED_PORT |
            Request::HEADER_X_FORWARDED_PROTO |
            Request::HEADER_X_FORWARDED_AWS_ELB
        );

        // Prepend agar header no-cache ditetapkan paling terakhir (menang atas
        // header yang di-set middleware lain seperti StartSession).
        $middleware->web(prepend: [
            NoCacheHeaders::class,
        ]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Tampilkan halaman error Inertia yang ramah (403/404/419/500)
        // alih-alih modal "This action is unauthorized" yang membingungkan.
        $exceptions->render(function (Throwable $e, Request $request) {
            // Biarkan request JSON/API mengalir ke handler default
            if ($request->expectsJson() || $request->is('api/*')) {
                return null;
            }

            $status = match (true) {
                $e instanceof AuthorizationException,
                $e instanceof AccessDeniedHttpException => 403,
                $e instanceof NotFoundHttpException => 404,
                $e instanceof HttpException && $e->getStatusCode() === 419 => 419,
                $e instanceof HttpException
                    && in_array($e->getStatusCode(), [500, 503]) => $e->getStatusCode(),
                default => null,
            };

            if ($status === null) {
                return null;
            }

            if (! $request->user()) {
                return null;
            }

            try {
                return Inertia::render('Errors/Error', [
                    'status' => $status,
                    'title' => $status === 403 ? 'Akses Ditolak' : null,
                ])->toResponse($request)->setStatusCode($status);
            } catch (Throwable $t) {
                Log::error('Error page render failed: '.$t->getMessage().' @ '.$t->getFile().':'.$t->getLine());

                return null;
            }
        });
    })->create();
