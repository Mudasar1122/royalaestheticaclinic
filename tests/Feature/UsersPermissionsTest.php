<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersPermissionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_save_explicit_empty_permissions_for_a_module(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'role' => 'staff',
            'module_access' => ['lead_management', 'campaign_management'],
            'module_permissions' => [
                'lead_management' => ['view_leads', 'create_lead', 'edit_lead'],
                'campaign_management' => ['view_campaigns', 'send_email_campaign'],
            ],
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(route('usersPermissionsUpdate', $user), [
                'permissions' => [
                    'campaign_management' => ['view_campaigns'],
                ],
            ]);

        $response->assertRedirect(route('usersList'));
        $response->assertSessionHas('status', 'Permissions updated successfully.');

        $user->refresh();

        $this->assertSame([
            'lead_management' => [],
            'campaign_management' => ['view_campaigns'],
        ], $user->module_permissions);
        $this->assertFalse($user->hasModulePermission('lead_management', 'view_leads'));
        $this->assertTrue($user->hasModulePermission('campaign_management', 'view_campaigns'));

        $this->actingAs($admin)
            ->get(route('usersPermissionsEdit', $user))
            ->assertViewHas('selectedPermissions', function (array $selectedPermissions): bool {
                return ($selectedPermissions['lead_management'] ?? null) === []
                    && ($selectedPermissions['campaign_management'] ?? null) === ['view_campaigns'];
            });
    }

    public function test_edit_user_keeps_explicitly_empty_module_permissions(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $user = User::factory()->create([
            'name' => 'Staff User',
            'email' => 'staff@example.com',
            'role' => 'staff',
            'module_access' => ['lead_management'],
            'module_permissions' => [
                'lead_management' => [],
            ],
            'is_active' => true,
        ]);

        $response = $this
            ->actingAs($admin)
            ->put(route('usersUpdate', $user), [
                'name' => 'Staff User Updated',
                'email' => 'staff@example.com',
                'phone' => '',
                'role' => 'staff',
                'module_access' => ['lead_management'],
                'is_active' => '1',
            ]);

        $response->assertRedirect(route('usersList'));
        $response->assertSessionHas('status', 'User updated successfully.');

        $user->refresh();

        $this->assertSame([
            'lead_management' => [],
        ], $user->module_permissions);
        $this->assertFalse($user->hasModulePermission('lead_management', 'view_leads'));
    }
}
