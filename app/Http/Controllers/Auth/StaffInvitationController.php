<?php

namespace App\Http\Controllers\Auth;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StaffInvitationController extends Controller
{
    public function create(Request $request, string $token): View|RedirectResponse
    {
        $email = $request->string('email')->toString();
        $user = User::query()->where('email', $email)->where('role', Role::Clinician)->first();

        if ($user === null || $user->invitation_accepted_at !== null || ! Password::broker()->tokenExists($user, $token)) {
            return redirect()->route('staff.login')->withErrors([
                'email' => 'This invitation link is invalid, expired, or has already been used.',
            ]);
        }

        return view('auth.accept-invitation', [
            'token' => $token,
            'email' => $email,
            'expiresInMinutes' => (int) config('auth.passwords.users.expire', 60),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email:rfc'],
            'password' => ['required', 'confirmed', 'min:12'],
        ]);

        $user = User::query()
            ->where('email', $request->string('email')->toString())
            ->where('role', Role::Clinician)
            ->first();

        if ($user === null || $user->invitation_accepted_at !== null) {
            return back()->withInput($request->only('email'))->withErrors([
                'email' => 'This invitation link is invalid, expired, or has already been used.',
            ]);
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                    'invitation_accepted_at' => now(),
                ])->save();

                event(new PasswordReset($user));
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            return back()->withInput($request->only('email'))->withErrors(['email' => __($status)]);
        }

        $user = $user->fresh();
        Auth::login($user);
        $request->session()->regenerate();
        $request->session()->forget('url.intended');

        return redirect()->to($user->panelRoute());
    }
}
