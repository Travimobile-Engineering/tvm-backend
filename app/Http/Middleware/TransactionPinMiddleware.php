<?php

namespace App\Http\Middleware;

use App\Models\User;
use App\Trait\HttpResponse;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class TransactionPinMiddleware
{
    use HttpResponse;

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = User::with('driverPin')
            ->where('id', $request->user()->id)
            ->first();

        if($user) {
            $pin = $user?->driverPin?->pin;

            if(Hash::check($request->pin, $pin)) {
                return $next($request);
            }
            
            $attempt = $user->driverPin?->attempts;
            $total = $attempt + 1;

            $user->driverPin()->update([
                'attempts' => $total
            ]);

            return $this->error(null, 'Invalid transaction pin', 403);
        }

        return $this->error(null, 'User does not exists', 404);
    }
}
