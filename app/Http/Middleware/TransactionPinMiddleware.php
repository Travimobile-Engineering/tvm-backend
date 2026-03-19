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
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $userId = $request->user_id ?? $request->user()->id;

        // Auth::guard('api')->user();

        $user = User::with('userPin')
            ->where('id', $userId)
            ->first();

        if ($user) {
            $pin = $user->userPin?->pin;

            if (Hash::check($request->pin, $pin)) {
                return $next($request);
            }

            $attempt = $user->userPin?->attempts;
            $total = $attempt + 1;

            $user->userPin()->update([
                'attempts' => $total,
            ]);

            return $this->error(null, 'Invalid transaction pin', 403);
        }

        return $this->error(null, 'User does not exists', 404);
    }
}
