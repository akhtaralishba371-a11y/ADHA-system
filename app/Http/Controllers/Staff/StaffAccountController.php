<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateStaffAccountRequest;
use App\Http\Requests\UpdateStaffPasswordRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class StaffAccountController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->json($this->accountPayload($request->user()));
    }

    public function update(UpdateStaffAccountRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->safe()->only(['name', 'email']);
        $oldAvatarPath = $user->getRawOriginal('avatar_path');
        $newAvatarPath = $request->file('avatar')?->store('avatars', 'public');

        if ($newAvatarPath !== null) {
            $data['avatar_path'] = $newAvatarPath;
        }

        try {
            $user->update($data);
        } catch (\Throwable $exception) {
            if ($newAvatarPath !== null) {
                Storage::disk('public')->delete($newAvatarPath);
            }

            throw $exception;
        }

        if ($newAvatarPath !== null && $oldAvatarPath !== null) {
            Storage::disk('public')->delete($oldAvatarPath);
        }

        return response()->json([
            ...$this->accountPayload($user->fresh()),
            'message' => 'Account updated.',
        ]);
    }

    public function updatePassword(UpdateStaffPasswordRequest $request): JsonResponse
    {
        $request->user()->update([
            'password' => $request->string('password')->toString(),
        ]);

        return response()->json(['message' => 'Password updated.']);
    }

    /**
     * @return array{id: int, name: string, email: string, role: ?string, clinician_id: ?int, avatar_url: ?string, initials: string}
     */
    private function accountPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role?->value,
            'clinician_id' => $user->clinician?->id,
            'avatar_url' => $user->avatar_url,
            'initials' => $user->initials,
        ];
    }
}
