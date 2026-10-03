<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Enums\Role;
use Database\Factories\UserFactory;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;

#[Fillable(['name', 'email', 'password', 'avatar_path'])]
#[Hidden(['password', 'remember_token', 'avatar_path'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'invitation_accepted_at' => 'datetime',
            'password' => 'hashed',
            'role' => Role::class,
        ];
    }

    public function hasAcceptedInvitation(): bool
    {
        return $this->invitation_accepted_at !== null;
    }

    protected function avatarUrl(): Attribute
    {
        return Attribute::get(fn (): ?string => $this->avatar_path === null
            ? null
            : Storage::disk('public')->url($this->avatar_path));
    }

    protected function initials(): Attribute
    {
        return Attribute::get(fn (): string => Str::of((string) $this->name)
            ->explode(' ')
            ->filter()
            ->take(2)
            ->map(fn (string $part): string => Str::upper(Str::substr($part, 0, 1)))
            ->implode('') ?: 'CT');
    }

    public function isStaff(): bool
    {
        if (! $this->role instanceof Role || ! $this->role->isStaff()) {
            return false;
        }

        if ($this->role === Role::Clinician && ! $this->hasAcceptedInvitation()) {
            return false;
        }

        return $this->role !== Role::Clinician || $this->clinician === null || $this->clinician->is_active;
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === Role::SuperAdmin;
    }

    public function isAdmin(): bool
    {
        return $this->role === Role::Admin;
    }

    public function isCareCoordinator(): bool
    {
        return $this->role === Role::CareCoordinator;
    }

    public function isClinician(): bool
    {
        return $this->role === Role::Clinician;
    }

    public function managesClinicians(): bool
    {
        return $this->role?->managesClinicians() ?? false;
    }

    public function accessesAdminPanel(): bool
    {
        return $this->isStaff() && $this->managesClinicians();
    }

    public function accessesClinicianPanel(): bool
    {
        return $this->isStaff() && $this->isClinician();
    }

    public function panelRoute(): string
    {
        return $this->accessesAdminPanel()
            ? route('admin.panel')
            : route('clinician.panel');
    }

    public function canAccessPanelUrl(string $url): bool
    {
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        if (str_starts_with($path, '/admin')) {
            return $this->accessesAdminPanel();
        }

        if (str_starts_with($path, '/clinician')) {
            return $this->accessesClinicianPanel();
        }

        return false;
    }

    public function hasRole(Role ...$roles): bool
    {
        return $this->role instanceof Role && in_array($this->role, $roles, true);
    }

    public function clinician(): HasOne
    {
        return $this->hasOne(Clinician::class);
    }

    public function sendPasswordResetNotification($token): void
    {
        $url = URL::temporarySignedRoute('staff.password.reset', now()->addMinutes(config('auth.passwords.users.expire')), [
            'token' => $token,
            'email' => $this->email,
        ]);

        $this->notify(new ResetPassword($url));
    }
}
