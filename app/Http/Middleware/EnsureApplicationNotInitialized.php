<?php

namespace App\Http\Middleware;

use App\Enums\Role;
use App\Models\User;
use App\Support\SettingsRepository;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicationNotInitialized
{
    public function __construct(private SettingsRepository $settings) {}

    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $hasSuperAdmin = User::query()->where('role', Role::SuperAdmin)->exists();

        if ($hasSuperAdmin && ! $this->settings->isInitialized()) {
            $this->settings->put(SettingsRepository::APP_INITIALIZED, true);
        }

        if ($this->settings->isInitialized() || $hasSuperAdmin) {
            if ($request->user()?->isStaff()) {
                return redirect()->to($request->user()->panelRoute());
            }

            return redirect()->route('staff.login');
        }

        return $next($request);
    }
}
