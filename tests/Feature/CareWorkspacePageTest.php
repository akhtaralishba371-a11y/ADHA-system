<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CareWorkspacePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_care_workspace_page_and_assets_are_available(): void
    {
        $staffMember = User::factory()->create(['role' => 'care_coordinator']);

        $this->actingAs($staffMember)->get(route('admin.panel'))
            ->assertOk()
            ->assertSee('Jelly')
            ->assertSee('Admin workspace')
            ->assertSee(route('adhd.crm.styles'), false)
            ->assertSee(route('adhd.crm.scripts'), false)
            ->assertSee('admin\/clinicians', false);

        $this->actingAs($staffMember)->get('/care-workspace')
            ->assertRedirect(route('admin.panel'));

        $this->get('/care-workspace/styles.css')
            ->assertOk()
            ->assertHeader('content-type', 'text/css; charset=UTF-8');

        $this->actingAs($staffMember)->get('/care-workspace/script.js')
            ->assertOk()
            ->assertHeader('content-type', 'text/javascript; charset=UTF-8');

        $this->actingAs($staffMember)->get('/care-workspace/assets/dr-amelia-clarke.png')
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
        $this->actingAs($staffMember)->get('/care-workspace/assets/dr-oliver-bennett.png')
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
        $this->actingAs($staffMember)->get('/care-workspace/assets/dr-sophie-williams.png')
            ->assertOk()
            ->assertHeader('content-type', 'image/png');
        $this->actingAs($staffMember)->get('/care-workspace/assets/not-allowed.png')->assertNotFound();

        $this->get('/booking.js')
            ->assertOk()
            ->assertHeader('content-type', 'text/javascript; charset=UTF-8');
    }
}
