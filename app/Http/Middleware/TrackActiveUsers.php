<?php

namespace App\Http\Middleware;

use App\Services\UserPresenceService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Symfony\Component\HttpFoundation\Response;

class TrackActiveUsers
{
    public function handle(Request $request, Closure $next): Response
    {
        // Register user as active
        $presenceService = App::make(UserPresenceService::class);
        $presenceService->registerUser();

        return $next($request);
    }
}
