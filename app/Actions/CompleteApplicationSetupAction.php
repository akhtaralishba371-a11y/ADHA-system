<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\User;
use App\Support\SettingsRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CompleteApplicationSetupAction
{
    public function __construct(private SettingsRepository $settings) {}

    /**
     * @param  array{clinic_name: string, name: string, email: string, password: string}  $data
     */
    public function handle(array $data): User
    {
        return Cache::lock('application-setup', 10)->block(5, function () use ($data): User {
            if ($this->settings->isInitialized() || User::query()->where('role', Role::SuperAdmin)->exists()) {
                throw ValidationException::withMessages([
                    'email' => 'This application has already been installed.',
                ]);
            }

            return DB::transaction(function () use ($data): User {
                if ($this->settings->isInitialized() || User::query()->where('role', Role::SuperAdmin)->lockForUpdate()->exists()) {
                    throw ValidationException::withMessages([
                        'email' => 'This application has already been installed.',
                    ]);
                }

                $user = new User([
                    'name' => $data['name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                ]);
                $user->role = Role::SuperAdmin;
                $user->invitation_accepted_at = now();
                $user->save();

                $this->settings->put(SettingsRepository::APP_NAME, $data['clinic_name']);
                $this->settings->put(SettingsRepository::APP_INITIALIZED, true);

                return $user;
            });
        });
    }
}
