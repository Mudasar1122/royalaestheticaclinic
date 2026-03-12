<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_users_list_shows_login_as_user_for_non_admin_accounts_only(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]);

        $otherAdmin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('usersList'));

        $response->assertOk();
        $response->assertSee(route('usersImpersonate', $staff), false);
        $response->assertDontSee(route('usersImpersonate', $otherAdmin), false);
        $response->assertSee('Login As User');
    }

    public function test_admin_can_impersonate_non_admin_user(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->post(route('usersImpersonate', $staff));

        $response->assertRedirect(route('index'));
        $response->assertSessionHas('status', 'Logged in as '.$staff->name.'.');
        $this->assertAuthenticatedAs($staff);
        $this->assertSame($admin->id, session('impersonator_id'));

        $this->get(route('index'))
            ->assertOk()
            ->assertSee('Back to Admin');
    }

    public function test_admin_can_return_from_impersonated_session(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $staff = User::factory()->create([
            'role' => 'staff',
            'is_active' => true,
        ]);

        $this
            ->actingAs($admin)
            ->post(route('usersImpersonate', $staff))
            ->assertRedirect(route('index'));

        $response = $this->post(route('usersStopImpersonation'));

        $response->assertRedirect(route('usersList'));
        $response->assertSessionHas('status', 'Returned to admin account.');
        $this->assertAuthenticatedAs($admin);
        $this->assertNull(session('impersonator_id'));
    }

    public function test_admin_cannot_impersonate_another_admin(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $otherAdmin = User::factory()->create([
            'role' => 'admin',
            'is_active' => true,
        ]);

        $response = $this
            ->from(route('usersList'))
            ->actingAs($admin)
            ->post(route('usersImpersonate', $otherAdmin));

        $response->assertRedirect(route('usersList'));
        $response->assertSessionHasErrors('impersonate');
        $this->assertAuthenticatedAs($admin);
    }
}
