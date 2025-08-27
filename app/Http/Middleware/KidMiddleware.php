<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class KidMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        if (!Auth::check() || Auth::user()->type !== 'child') {
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Unauthorized. Kid access only.'], 403);
            }
            return redirect('/');
        }

        return $next($request);
    }
}
