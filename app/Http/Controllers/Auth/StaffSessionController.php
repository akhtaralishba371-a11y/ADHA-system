<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStaffLoginRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class StaffSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.staff-login');
    }

    public function store(StoreStaffLoginRequest $request): JsonResponse|RedirectResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);
        $remember = $request->boolean('remember');

        if (! Auth::attempt($credentials, $remember) || ! $request->user()?->isStaff()) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => 'The supplied credentials are not valid for the care workspace.',
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();
        $panelRoute = $user->panelRoute();

        if ($request->expectsJson()) {
            return response()->json(['redirect' => $panelRoute]);
        }

        $intended = $request->session()->pull('url.intended');

        if (is_string($intended) && $intended !== '' && $user->canAccessPanelUrl($intended)) {
            return redirect()->to($intended);
        }

        return redirect()->to($panelRoute);
    }

    public function destroy(): JsonResponse|RedirectResponse
    {
        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        if (request()->expectsJson()) {
            return response()->json([], 204);
        }

        return redirect()->route('staff.login');
    }
}
