<?php

namespace App\Http\Controllers;

use App\Actions\CompleteApplicationSetupAction;
use App\Http\Requests\StoreSetupRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SetupController extends Controller
{
    public function create(): View
    {
        return view('setup.create');
    }

    public function store(StoreSetupRequest $request, CompleteApplicationSetupAction $completeSetup): RedirectResponse
    {
        $user = $completeSetup->handle($request->validated());

        auth()->login($user);
        $request->session()->regenerate();

        return redirect()->route('admin.panel')
            ->with('status', 'Installation complete. Configure outgoing email in Settings when ready.');
    }
}
