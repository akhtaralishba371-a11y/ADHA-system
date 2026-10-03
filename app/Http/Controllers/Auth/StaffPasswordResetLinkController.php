<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\View\View;

class StaffPasswordResetLinkController extends Controller
{
    public function create(): View
    {
        return view('auth.forgot-password');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email:rfc', 'max:255']]);

        $staffUser = User::query()->where('email', $request->string('email')->toString())->first();
        if ($staffUser !== null && $staffUser->isStaff()) {
            Password::sendResetLink(['email' => $staffUser->email]);
        }

        return back()->with('status', 'If an authorised staff account matches that address, a reset link has been sent.');
    }
}
