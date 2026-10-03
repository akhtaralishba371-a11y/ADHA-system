<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminPanelAccess
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->accessesAdminPanel()) {
            return $next($request);
        }

        if ($user?->accessesClinicianPanel()) {
            if ($request->expectsJson()) {
                abort(403);
            }

            return redirect()->to($user->panelRoute());
        }

        abort(403);
    }
}
