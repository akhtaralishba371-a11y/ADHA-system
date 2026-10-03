<?php

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:create-staff-user {--name=} {--email=} {--role=care_coordinator}')]
#[Description('Create an authorised staff account for the care workspace')]
class CreateStaffUser extends Command
{
    public function handle(): int
    {
        $name = $this->option('name') ?: $this->ask('Staff member name');
        $email = $this->option('email') ?: $this->ask('Staff member email');
        $roleOption = $this->option('role');
        $role = is_string($roleOption) ? Role::tryFrom($roleOption) : null;

        if (! is_string($name) || ! is_string($email) || filter_var($email, FILTER_VALIDATE_EMAIL) === false || $role === null) {
            $this->error('Provide a valid name, email, and staff role ('.implode(', ', Role::staffValues()).').');

            return self::FAILURE;
        }

        if (User::query()->where('email', $email)->exists()) {
            $this->error('A user with that email address already exists.');

            return self::FAILURE;
        }

        $password = $this->secret('Password (minimum 12 characters)');

        if (! is_string($password) || strlen($password) < 12) {
            $this->error('The password must be at least 12 characters long.');

            return self::FAILURE;
        }

        $user = new User([
            'name' => $name,
            'email' => $email,
            'password' => $password,
        ]);
        $user->role = $role;
        $user->save();

        $this->info('Staff user created.');

        return self::SUCCESS;
    }
}
