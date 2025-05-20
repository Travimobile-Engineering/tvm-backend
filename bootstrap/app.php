<?php

use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Application;
use App\Http\Middleware\TransactionPinMiddleware;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'transaction.pin' => TransactionPinMiddleware::class,
            'cacheResponse' => \Spatie\ResponseCache\Middlewares\CacheResponse::class,
            'doNotCacheResponse' => \Spatie\ResponseCache\Middlewares\DoNotCacheResponse::class,
            'agent.auth' => \App\Http\Middleware\AgentAuthMiddleware::class,
            'validate.header' => \App\Http\Middleware\ValidateHeader::class,
            'impersonation.throttle' => \App\Http\Middleware\ImpersonationThrottle::class,
            'verify.pin' => \App\Http\Middleware\VerifyPinChange::class,
            'login.attempt' => \App\Http\Middleware\LoginAttempt::class,
            'auth.micro' => \App\Http\Middleware\AuthenticateViaAuthService::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // $exceptions->reportable(function (Throwable $e): void {
        //     Log::channel('slack')->error($e->getMessage(),[
        //         'file' => $e->getFile(),
        //         'Line' => $e->getLine(),
        //         'code' => $e->getCode(),
        //         'url' => request()->fullUrl(),
        //     ]);
        // });

        $exceptions->renderable(function (NotFoundHttpException $e, $request) {
            // Handle JSON request 404's
            if ($request->json()) {
                return response()->json(['message' => 'Resource was not Found'], 404);
            }

            throw $e;
        });

        $exceptions->shouldRenderJsonWhen(function ($request) {
            return $request->is('api/*') || $request->expectsJson();
        });

    })->create();
