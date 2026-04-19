<?php

namespace App\Http\Middleware;

use App\Models\Airline;
use App\Trait\HttpResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAirlineWalletBalance
{
    use HttpResponse;

    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $airlineId = $request->input('airline_id')
            ?? $request->query('airline_id')
            ?? $request->route('airline_id');

        if (! $airlineId) {
            return $this->error(null, 'Airline ID is required', 400);
        }

        $airline = Airline::with('wallet')->find($airlineId);

        if (! $airline) {
            return $this->error(null, 'Airline not found', 404);
        }

        $wallet = $airline->currentWallet();

        // Ensure wallet exists
        if (! $wallet) {
            return $this->error(null, 'Airline wallet not found', 404);
        }

        $minimumBalance = 1000;

        if ($wallet->balance < $minimumBalance) {
            return $this->error(null, 'Insufficient balance', 422);
        }

        return $next($request);
    }
}
