<?php

namespace App\Http\Controllers;

use App\Models\Clinician;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdhdController extends Controller
{
    public function index(): View
    {
        return view('adhd.index', ['clinicians' => $this->publicClinicians()]);
    }

    public function clinicians(): View
    {
        return view('adhd.clinicians', ['clinicians' => $this->publicClinicians()]);
    }

    public function howItWorks(): View
    {
        return view('adhd.how-it-works');
    }

    public function adminWorkspace(): View
    {
        return view('admin.panel', $this->crmPanelData('admin'));
    }

    public function clinicianWorkspace(): View
    {
        return view('clinician.panel', $this->crmPanelData('clinician'));
    }

    /**
     * @param  'admin'|'clinician'  $panel
     * @return array<string, mixed>
     */
    private function crmPanelData(string $panel): array
    {
        $user = auth()->user();
        $nameParts = collect(explode(' ', (string) $user->name))->filter()->take(2);
        $userInitials = $nameParts->isEmpty()
            ? 'CT'
            : $nameParts->map(fn (string $part): string => strtoupper(substr($part, 0, 1)))->implode('');

        return [
            'panel' => $panel,
            'title' => ($panel === 'admin' ? 'Admin workspace' : 'Clinician workspace').' · Jelly',
            'apiBase' => $panel === 'admin' ? url('/admin') : url('/clinician'),
            'workspaceLabel' => $panel === 'admin' ? 'Admin workspace' : 'Clinician workspace',
            'workspaceDetail' => 'London & Manchester',
            'workspacePill' => $panel === 'admin' ? 'Admin panel' : 'Clinician panel',
            'canManageClinicians' => $user->managesClinicians(),
            'canAccessSettings' => $user->isSuperAdmin(),
            'navigation' => $panel === 'admin'
                ? array_values(array_filter([
                    ['id' => 'overview', 'label' => 'Overview', 'icon' => 'overview'],
                    ['id' => 'patients', 'label' => 'Patients', 'icon' => 'patients'],
                    ['id' => 'appointments', 'label' => 'Appointments', 'icon' => 'calendar'],
                    ['id' => 'clinicians', 'label' => 'Clinicians', 'icon' => 'clinicians'],
                    ['id' => 'services', 'label' => 'Services', 'icon' => 'tasks'],
                    ['id' => 'followups', 'label' => 'Follow-ups', 'icon' => 'tasks'],
                    ['id' => 'account', 'label' => 'Account Settings', 'icon' => 'help'],
                    $user->isSuperAdmin() ? ['id' => 'settings', 'label' => 'Settings', 'icon' => 'help'] : null,
                ]))
                : [
                    ['id' => 'overview', 'label' => 'Dashboard', 'icon' => 'overview'],
                    ['id' => 'profile', 'label' => 'My Profile', 'icon' => 'clinicians'],
                    ['id' => 'availability', 'label' => 'My Availability', 'icon' => 'calendar'],
                    ['id' => 'appointments', 'label' => 'My Appointments', 'icon' => 'calendar'],
                    ['id' => 'patients', 'label' => 'My Patients', 'icon' => 'patients'],
                    ['id' => 'account', 'label' => 'Account Settings', 'icon' => 'help'],
                ],
            'user' => $user,
            'userInitials' => $userInitials,
            'userAvatarUrl' => $user->avatar_url,
            'roleLabel' => str($user->role?->value)->replace('_', ' ')->title()->toString(),
        ];
    }

    public function careWorkspaceStyles(): BinaryFileResponse
    {
        return $this->careWorkspaceFile('css/styles.css', 'text/css');
    }

    public function careWorkspaceScripts(): BinaryFileResponse
    {
        return $this->careWorkspaceFile('js/script.js', 'text/javascript');
    }

    public function bookingScripts(): BinaryFileResponse
    {
        return response()->file(
            public_path('assets/ADHD/js/script.js'),
            ['Content-Type' => 'text/javascript'],
        );
    }

    public function careWorkspaceAsset(string $filename): BinaryFileResponse
    {
        abort_unless(in_array($filename, [
            'dr-amelia-clarke.png',
            'dr-oliver-bennett.png',
            'dr-sophie-williams.png',
        ], true), 404);

        return response()->file(public_path("assets/ADHD/images/doctors/{$filename}"));
    }

    private function careWorkspaceFile(string $filename, string $contentType): BinaryFileResponse
    {
        return response()->file(
            public_path("assets/ADHD-CRM/{$filename}"),
            ['Content-Type' => $contentType],
        );
    }

    /**
     * @return Collection<int, Clinician>
     */
    private function publicClinicians(): Collection
    {
        return Clinician::query()
            ->publicDirectory()
            ->orderBy('display_name')
            ->get([
                'public_id',
                'display_name',
                'professional_title',
                'specialty',
                'location',
                'years_experience',
                'bio',
                'photo_path',
            ]);
    }
}
