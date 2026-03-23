<?php

use App\Http\Middleware\AgentAuthMiddleware;
use App\Http\Middleware\BurstGuard;
use App\Http\Middleware\DecryptIds;
use App\Http\Middleware\ForceProductionKey;
use App\Http\Middleware\ImpersonationThrottle;
use App\Http\Middleware\LoginAttempt;
use App\Http\Middleware\TransactionPinMiddleware;
use App\Http\Middleware\TransactionReplayShield;
use App\Http\Middleware\ValidateApiKey;
use App\Http\Middleware\ValidateHeader;
use App\Http\Middleware\VerifyPinChange;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Facades\Log;
use Spatie\ResponseCache\Middlewares\CacheResponse;
use Spatie\ResponseCache\Middlewares\DoNotCacheResponse;
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
            'cacheResponse' => CacheResponse::class,
            'doNotCacheResponse' => DoNotCacheResponse::class,
            'agent.auth' => AgentAuthMiddleware::class,
            'validate.header' => ValidateHeader::class,
            'impersonation.throttle' => ImpersonationThrottle::class,
            'verify.pin' => VerifyPinChange::class,
            'login.attempt' => LoginAttempt::class,
            'tx.replay' => TransactionReplayShield::class,
            'burst.guard' => BurstGuard::class,
            'decrypt.ids' => DecryptIds::class,
            'validate.api.key' => ValidateApiKey::class,
            'force.production.key' => ForceProductionKey::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->reportable(function (Throwable $e): void {
            Log::channel('slack')->error($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'code' => $e->getCode(),
                'url' => request()->fullUrl(),
                'input' => request()->all(),
            ]);
        });

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
