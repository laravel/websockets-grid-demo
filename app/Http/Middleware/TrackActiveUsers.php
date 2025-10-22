<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Symfony\Component\HttpFoundation\Response;

class TrackActiveUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!Session::has('grid_user_id')) {
            Session::put('grid_user_id', (string) \Str::uuid());
        }

        return $next($request);
    }
}
