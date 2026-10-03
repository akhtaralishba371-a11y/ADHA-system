<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class StaffAccountTest extends TestCase
{
    use RefreshDatabase;

    public function test_staff_can_view_account_details(): void
    {
        $user = User::factory()->admin()->create([
            'name' => 'Alex Admin',
            'email' => 'alex@example.test',
        ]);

        $this->actingAs($user)
            ->getJson(route('admin.account.show'))
            ->assertOk()
            ->assertJsonPath('name', 'Alex Admin')
            ->assertJsonPath('email', 'alex@example.test')
            ->assertJsonPath('role', 'admin')
            ->assertJsonPath('avatar_url', null)
            ->assertJsonPath('initials', 'AA');
    }

    public function test_staff_can_upload_profile_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->clinician()->create([
            'name' => 'Dr Sam Rivera',
            'email' => 'sam@example.test',
        ]);

        $this->actingAs($user)
            ->post(route('clinician.account.update'), [
                '_method' => 'PUT',
                'name' => 'Dr Sam Rivera',
                'email' => 'sam@example.test',
                'avatar' => UploadedFile::fake()->image('avatar.jpg', 200, 200),
            ])
            ->assertOk()
            ->assertJsonPath('name', 'Dr Sam Rivera')
            ->assertJsonStructure(['avatar_url']);

        $user->refresh();
        $this->assertNotNull($user->avatar_path);
        Storage::disk('public')->assertExists($user->avatar_path);
        $this->assertNotNull($user->avatar_url);
    }

    public function test_staff_can_update_account_without_replacing_avatar(): void
    {
        Storage::fake('public');
        $user = User::factory()->admin()->create([
            'name' => 'Alex Admin',
            'email' => 'alex@example.test',
            'avatar_path' => 'avatars/existing.jpg',
        ]);
        Storage::disk('public')->put('avatars/existing.jpg', 'fake');

        $this->actingAs($user)
            ->putJson(route('admin.account.update'), [
                'name' => 'Alex Updated',
                'email' => 'alex@example.test',
            ])
            ->assertOk()
            ->assertJsonPath('name', 'Alex Updated')
            ->assertJsonPath('avatar_url', Storage::disk('public')->url('avatars/existing.jpg'));

        $this->assertSame('avatars/existing.jpg', $user->fresh()->avatar_path);
        Storage::disk('public')->assertExists('avatars/existing.jpg');
    }
}
