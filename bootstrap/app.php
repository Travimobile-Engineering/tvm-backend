<?php

use App\Exceptions\NpisApiException;
use App\Http\Middleware\AgentAuthMiddleware;
use App\Http\Middleware\BurstGuard;
use App\Http\Middleware\CheckAirlineWalletBalance;
use App\Http\Middleware\CheckIpWhitelist;
use App\Http\Middleware\DecryptIds;
use App\Http\Middleware\Emf\EnsureEmfOfficer;
use App\Http\Middleware\Emf\EnsureEmfPermission;
use App\Http\Middleware\ForceProductionKey;
use App\Http\Middleware\ImpersonationThrottle;
use App\Http\Middleware\LoginAttempt;
use App\Http\Middleware\RateLimitByIp;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\TransactionPinMiddleware;
use App\Http\Middleware\TransactionReplayShield;
use App\Http\Middleware\ValidateApiKey;
use App\Http\Middleware\ValidateHeader;
use App\Http\Middleware\VerifyPinChange;
use App\Support\Npis\NpisResponseFactory;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);

        $middleware->alias([
            'transaction.pin' => TransactionPinMiddleware::class,
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
            'ip.whitelist' => CheckIpWhitelist::class,
            'ip.ratelimit' => RateLimitByIp::class,
            'airline.check.balance' => CheckAirlineWalletBalance::class,
            'emf.auth' => EnsureEmfOfficer::class,
            'emf.can' => EnsureEmfPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $npisError = static function ($request, int $status, string $code, string $message, array $details = [], ?int $retryAfter = null) {
            return app(NpisResponseFactory::class)
                ->apiError($request, $status, $code, $message, $details, $retryAfter);
        };

        $exceptions->renderable(function (NpisApiException $e, $request) use ($npisError) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return $npisError($request, $e->status, $e->errorCode, $e->getMessage(), $e->details, $e->retryAfterSeconds);
        });

        $exceptions->renderable(function (ValidationException $e, $request) use ($npisError) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return $npisError(
                $request,
                422,
                'VALIDATION_FAILED',
                'Some details need attention.',
                ['fields' => $e->errors()]
            );
        });

        $exceptions->renderable(function (AuthenticationException $e, $request) use ($npisError) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return $npisError($request, 401, 'UNAUTHENTICATED', 'Authentication is required.');
        });

        $exceptions->renderable(function (ThrottleRequestsException $e, $request) use ($npisError) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return $npisError($request, 429, 'RATE_LIMITED', 'Too many requests. Try again shortly.', [], (int) ($e->getHeaders()['Retry-After'] ?? 60));
        });

        $exceptions->renderable(function (AuthorizationException $e, $request) use ($npisError) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return $npisError($request, 403, 'FORBIDDEN', 'Your role does not have access to this action.');
        });

        $exceptions->renderable(function (MethodNotAllowedHttpException $e, $request) use ($npisError) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return $npisError($request, 405, 'METHOD_NOT_ALLOWED', 'This method is not supported for this endpoint.');
        });

        $exceptions->renderable(function (ModelNotFoundException $e, $request) use ($npisError) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return $npisError($request, 404, 'NOT_FOUND', 'Resource not found.');
        });

        $exceptions->renderable(function (NotFoundHttpException $e, $request) use ($npisError) {
            if (! $request->is('api/v1/*')) {
                return null;
            }

            return $npisError($request, 404, 'NOT_FOUND', 'Resource not found.');
        });

        $exceptions->reportable(function (Throwable $e): void {
            if (app()->isProduction()) {
                Log::channel('slack')->error($e->getMessage(), [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'code' => $e->getCode(),
                    'url' => request()->fullUrl(),
                    'input' => request()->all(),
                ]);
            }
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
