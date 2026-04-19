<?php

namespace App\Http\Middleware;

use App\Models\Airline;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class ForceProductionKey
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $airlineId = $request->route('airline_id') ??
            $request->query('airline_id') ??
            $request->input('airline_id');

        if (! $airlineId || ! is_numeric($airlineId)) {
            return $this->errorResponse('Airline ID not provided or invalid.', 403);
        }

        $airline = Cache::remember(
            "airline:{$airlineId}",
            now()->addMinutes(5),
            fn () => Airline::find((int) $airlineId)
        );

        if (! $airline) {
            return $this->errorResponse('Airline does not exist.', 404);
        }

        if ($airline->active_environment !== 'production') {
            return $this->errorResponse(
                'This airline is not currently on the production environment. Please switch environments to proceed.',
                403,
                'ENVIRONMENT_NOT_PRODUCTION'
            );
        }

        return $next($request);
    }

    private function errorResponse(string $message, int $status, ?string $code = null): Response
    {
        return response()->json(array_filter([
            'success' => false,
            'message' => $message,
            'code' => $code,
            'data' => null,
        ]), $status);
    }
}
