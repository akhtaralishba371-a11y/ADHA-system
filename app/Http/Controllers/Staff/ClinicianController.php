<?php

namespace App\Http\Controllers\Staff;

use App\Actions\CreateClinicianOnboardingAction;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreClinicianRequest;
use App\Http\Requests\UpdateClinicianRequest;
use App\Http\Resources\ClinicianResource;
use App\Models\Clinician;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class ClinicianController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', Clinician::class);

        $clinicians = Clinician::query()
            ->with('user:id,name,email,role,invitation_accepted_at')
            ->withCount('appointments')
            ->when($request->user()->isClinician(), fn (Builder $query): Builder => $query->where('user_id', $request->user()->id))
            ->orderBy('display_name')
            ->get();

        return ClinicianResource::collection($clinicians)->response();
    }

    public function store(StoreClinicianRequest $request): JsonResponse
    {
        $clinician = Clinician::query()->create($request->validated());

        return (new ClinicianResource($clinician->load('user:id,name,email,role,invitation_accepted_at')))->response()->setStatusCode(201);
    }

    public function show(Clinician $clinician): JsonResponse
    {
        Gate::authorize('view', $clinician);

        return (new ClinicianResource($clinician->load(['user:id,name,email,role,invitation_accepted_at', 'availabilities', 'timeOff'])))->response();
    }

    public function update(UpdateClinicianRequest $request, Clinician $clinician, CreateClinicianOnboardingAction $onboarding): JsonResponse
    {
        $isManager = $request->user()->managesClinicians();
        $data = $request->safe()->except(['photo', 'email']);
        if (! $isManager) {
            unset($data['is_active'], $data['is_public']);
        }

        $oldPhotoPath = $clinician->getRawOriginal('photo_path');
        $newPhotoPath = $request->file('photo')?->store('clinicians', 'public');
        $inviteUser = null;

        try {
            DB::transaction(function () use ($request, $clinician, $data, $newPhotoPath, $isManager, &$inviteUser): void {
                if ($newPhotoPath !== null) {
                    $data['photo_path'] = $newPhotoPath;
                }

                $clinician->update($data);

                if ($isManager && $clinician->user === null && $request->filled('email')) {
                    $user = new User([
                        'name' => $request->string('display_name')->toString() ?: $clinician->display_name,
                        'email' => $request->string('email')->toString(),
                        'password' => Str::password(32),
                    ]);
                    $user->role = Role::Clinician;
                    $user->invitation_accepted_at = null;
                    $user->save();
                    $clinician->update(['user_id' => $user->id]);
                    $inviteUser = $user;
                } elseif ($isManager && $clinician->user !== null) {
                    $account = [];
                    if ($request->filled('email')) {
                        $account['email'] = $request->string('email')->toString();
                    }
                    if ($request->filled('display_name')) {
                        $account['name'] = $request->string('display_name')->toString();
                    }
                    if ($account !== []) {
                        $clinician->user->update($account);
                    }
                } elseif (! $isManager && $request->filled('display_name') && $clinician->user !== null) {
                    $clinician->user->update(['name' => $request->string('display_name')->toString()]);
                }
            });
        } catch (Throwable $exception) {
            if ($newPhotoPath !== null) {
                Storage::disk('public')->delete($newPhotoPath);
            }

            throw $exception;
        }

        if ($newPhotoPath !== null && $oldPhotoPath !== null) {
            Storage::disk('public')->delete($oldPhotoPath);
        }

        if ($inviteUser !== null) {
            $onboarding->sendInvitation($inviteUser);
        }

        return (new ClinicianResource($clinician->refresh()->load(['user:id,name,email,role,invitation_accepted_at', 'availabilities', 'timeOff'])))->response();
    }

    public function destroy(Clinician $clinician): JsonResponse
    {
        Gate::authorize('delete', $clinician);
        $clinician->update(['is_active' => false]);

        return response()->json([], 204);
    }
}
