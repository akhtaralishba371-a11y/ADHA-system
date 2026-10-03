<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureClinicianPanelAccess
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->accessesClinicianPanel()) {
            return $next($request);
        }

        if ($user?->accessesAdminPanel()) {
            if ($request->expectsJson()) {
                abort(403);
            }

            return redirect()->to($user->panelRoute());
        }

        abort(403);
    }
}
