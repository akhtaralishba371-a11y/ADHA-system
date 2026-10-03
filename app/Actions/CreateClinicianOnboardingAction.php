<?php

namespace App\Actions;

use App\Enums\Role;
use App\Models\AuditLog;
use App\Models\Clinician;
use App\Models\User;
use App\Notifications\ClinicianInvitation;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Throwable;

class CreateClinicianOnboardingAction
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function handle(array $data, ?UploadedFile $photo, User $actor): Clinician
    {
        $photoPath = $photo?->store('clinicians', 'public');

        try {
            $clinician = DB::transaction(function () use ($data, $photoPath, $actor): Clinician {
                $user = new User([
                    'name' => $data['display_name'],
                    'email' => $data['email'],
                    'password' => Str::password(32),
                ]);
                $user->role = Role::Clinician;
                $user->invitation_accepted_at = null;
                $user->save();

                $clinician = Clinician::query()->create([
                    'user_id' => $user->id,
                    'display_name' => $data['display_name'],
                    'professional_title' => $data['professional_title'],
                    'specialty' => $data['specialty'],
                    'location' => $data['location'] ?? null,
                    'years_experience' => $data['years_experience'] ?? null,
                    'bio' => $data['bio'] ?? null,
                    'photo_path' => $photoPath,
                    'is_active' => (bool) $data['is_active'],
                    'is_public' => (bool) $data['is_public'],
                ]);

                $clinician->availabilities()->createMany(collect($data['availability'])->map(fn (array $availability): array => [
                    ...$availability,
                    'location' => $availability['format'] === 'online' ? null : ($availability['location'] ?? null),
                    'is_active' => (bool) $availability['is_active'],
                ])->all());

                if (! empty($data['time_off'])) {
                    $clinician->timeOff()->createMany($data['time_off']);
                }

                AuditLog::query()->create([
                    'actor_id' => $actor->id,
                    'event' => 'clinician.onboarded',
                    'auditable_type' => Clinician::class,
                    'auditable_id' => $clinician->id,
                    'metadata' => ['user_id' => $user->id],
                ]);

                return $clinician->load(['user:id,name,email,role,invitation_accepted_at', 'availabilities', 'timeOff']);
            });

            $this->sendInvitation($clinician->user);

            return $clinician;
        } catch (Throwable $exception) {
            if ($photoPath !== null) {
                Storage::disk('public')->delete($photoPath);
            }

            throw $exception;
        }
    }

    public function sendInvitation(User $user): void
    {
        $expiresInMinutes = (int) config('auth.passwords.users.expire', 60);
        $token = Password::broker()->createToken($user);
        $url = URL::temporarySignedRoute(
            'staff.invitation.show',
            now()->addMinutes($expiresInMinutes),
            ['token' => $token, 'email' => $user->email],
        );

        // Send immediately so local installs do not depend on queue:work.
        $user->notifyNow(new ClinicianInvitation($url, $expiresInMinutes));
    }
}
