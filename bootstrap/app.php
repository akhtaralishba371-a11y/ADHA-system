<?php

use App\Http\Middleware\EnsureAdminPanelAccess;
use App\Http\Middleware\EnsureApplicationNotInitialized;
use App\Http\Middleware\EnsureClinicianPanelAccess;
use App\Http\Middleware\EnsureStaffUser;
use App\Http\Middleware\EnsureSuperAdmin;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->redirectGuestsTo(fn (): string => route('staff.login'));
        $middleware->redirectUsersTo(function (Request $request): string {
            $user = $request->user();

            if ($user !== null && method_exists($user, 'panelRoute') && $user->isStaff()) {
                return $user->panelRoute();
            }

            return route('adhd.home');
        });
        $middleware->alias([
            'staff' => EnsureStaffUser::class,
            'admin.panel' => EnsureAdminPanelAccess::class,
            'clinician.panel' => EnsureClinicianPanelAccess::class,
            'setup.available' => EnsureApplicationNotInitialized::class,
            'super.admin' => EnsureSuperAdmin::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
