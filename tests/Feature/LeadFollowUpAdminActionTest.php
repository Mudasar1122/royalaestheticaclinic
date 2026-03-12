<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadFollowUpAdminActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_sees_edit_and_delete_actions_on_follow_up_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        [$contact, $lead] = $this->createLead();
        $followUp = FollowUp::query()->create([
            'lead_id' => $lead->id,
            'contact_id' => $contact->id,
            'trigger_type' => 'manual_lead_create',
            'stage_snapshot' => 'new',
            'status' => 'pending',
            'due_at' => now()->addDay(),
            'summary' => 'Initial remarks',
            'created_by_user_id' => $admin->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('clinicLeadFollowUp', $lead));

        $response->assertOk();
        $response->assertSee(route('clinicLeadDestroy', $lead), false);
        $response->assertSee('data-modal-target="editLeadModal-' . $lead->id . '"', false);
        $response->assertSee(route('clinicLeadUpdate', $lead), false);
        $response->assertSee('Created Date');
        $response->assertSee('Edit Follow-up');
        $response->assertSee(route('clinicFollowUpUpdate', $followUp), false);
        $response->assertSee('Edit');
        $response->assertSee('Delete');
    }

    public function test_non_admin_does_not_see_admin_actions_on_follow_up_page(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'module_access' => ['lead_management'],
            'module_permissions' => [
                'lead_management' => ['manage_followups'],
            ],
        ]);

        [$contact, $lead] = $this->createLead();
        FollowUp::query()->create([
            'lead_id' => $lead->id,
            'contact_id' => $contact->id,
            'trigger_type' => 'manual_lead_create',
            'stage_snapshot' => 'new',
            'status' => 'pending',
            'due_at' => now()->addDay(),
            'summary' => 'Initial remarks',
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('clinicLeadFollowUp', $lead));

        $response->assertOk();
        $response->assertDontSee('id="lead-follow-up-action-menu-' . $lead->id . '"', false);
        $response->assertDontSee('data-modal-target="editLeadModal-' . $lead->id . '"', false);
        $response->assertDontSee('id="editLeadModal-' . $lead->id . '"', false);
        $response->assertDontSee('Edit Follow-up');
    }

    public function test_admin_can_delete_lead_from_follow_up_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        [$contact, $lead] = $this->createLead();

        $response = $this
            ->actingAs($admin)
            ->delete(route('clinicLeadDestroy', $lead));

        $response->assertRedirect(route('clinicLeads'));
        $response->assertSessionHas('status', 'Lead deleted.');
        $this->assertSoftDeleted('leads', ['id' => $lead->id]);
        $this->assertDatabaseHas('contacts', ['id' => $contact->id]);
    }

    public function test_non_admin_cannot_delete_lead(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'module_access' => ['lead_management'],
            'module_permissions' => [
                'lead_management' => ['manage_followups'],
            ],
        ]);

        [, $lead] = $this->createLead();

        $response = $this
            ->actingAs($user)
            ->delete(route('clinicLeadDestroy', $lead));

        $response->assertForbidden();
        $this->assertDatabaseHas('leads', ['id' => $lead->id]);
    }

    public function test_admin_can_view_and_restore_deleted_leads(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        [$contact, $lead] = $this->createLead();
        $lead->delete();

        $listResponse = $this
            ->actingAs($admin)
            ->get(route('clinicDeletedLeads'));

        $listResponse->assertOk();
        $listResponse->assertSee('Deleted Leads');
        $listResponse->assertSee($contact->full_name);
        $listResponse->assertSee(route('clinicLeadRestore', $lead->id), false);
        $listResponse->assertSee('Restore');

        $restoreResponse = $this
            ->actingAs($admin)
            ->patch(route('clinicLeadRestore', $lead->id));

        $restoreResponse->assertRedirect(route('clinicDeletedLeads'));
        $restoreResponse->assertSessionHas('status', 'Lead restored.');
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'deleted_at' => null]);
    }

    public function test_non_admin_cannot_view_or_restore_deleted_leads(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'module_access' => ['lead_management'],
            'module_permissions' => [
                'lead_management' => ['manage_followups'],
            ],
        ]);

        [, $lead] = $this->createLead();
        $lead->delete();

        $this
            ->actingAs($user)
            ->get(route('clinicDeletedLeads'))
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->patch(route('clinicLeadRestore', $lead->id))
            ->assertForbidden();
    }

    public function test_admin_can_update_procedure_interests_from_follow_up_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        [, $lead] = $this->createLead();

        $response = $this
            ->from(route('clinicLeadFollowUp', $lead))
            ->actingAs($admin)
            ->patch(route('clinicLeadUpdate', $lead), [
                'full_name' => 'Ayesha Noor',
                'gender' => 'female',
                'phone' => '+923009998887',
                'email' => '',
                'source_platform' => 'manual',
                'stage' => 'new',
                'status' => 'open',
                'procedure_interests_submitted' => '1',
                'procedure_interests' => ['laser_hair_removal', 'other'],
                'procedure_other' => 'Hydra Facial',
            ]);

        $response->assertRedirect(route('clinicLeadFollowUp', $lead));
        $response->assertSessionHas('status', 'Lead updated.');

        $lead->refresh();

        $this->assertSame(['laser_hair_removal', 'other'], data_get($lead->meta, 'procedures_of_interest'));
        $this->assertSame('Hydra Facial', data_get($lead->meta, 'procedure_other'));
        $this->assertSame('Ayesha Noor', $lead->contact?->full_name);
    }

    public function test_admin_can_update_follow_up_from_follow_up_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'module_permissions' => [
                'lead_management' => ['mark_booked'],
            ],
        ]);

        [, $lead] = $this->createLead();

        $followUp = FollowUp::query()->create([
            'lead_id' => $lead->id,
            'contact_id' => $lead->contact_id,
            'trigger_type' => 'manual_lead_create',
            'stage_snapshot' => 'new',
            'status' => 'pending',
            'due_at' => now()->addDay(),
            'summary' => 'Initial remarks',
            'created_by_user_id' => $admin->id,
            'assigned_to_user_id' => $admin->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->patch(route('clinicFollowUpUpdate', $followUp), [
                'follow_up_method' => 'call',
                'stage' => 'contacted',
                'next_follow_up_due_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'remarks' => 'Updated remarks',
                'follow_up_id' => $followUp->id,
                'follow_up_edit_submission' => '1',
            ]);

        $response->assertRedirect(route('clinicLeadFollowUp', $lead));
        $response->assertSessionHas('status', 'Follow-up updated.');

        $followUp->refresh();
        $lead->refresh();

        $this->assertSame('call', $followUp->trigger_type);
        $this->assertSame('contacted', $followUp->stage_snapshot);
        $this->assertSame('Updated remarks', $followUp->summary);
        $this->assertSame('contacted', $lead->stage);
        $this->assertSame('open', $lead->status);
    }

    public function test_non_admin_cannot_update_follow_up_from_follow_up_page(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'module_access' => ['lead_management'],
            'module_permissions' => [
                'lead_management' => ['manage_followups'],
            ],
        ]);

        [$contact, $lead] = $this->createLead();

        $followUp = FollowUp::query()->create([
            'lead_id' => $lead->id,
            'contact_id' => $contact->id,
            'trigger_type' => 'manual_lead_create',
            'stage_snapshot' => 'new',
            'status' => 'pending',
            'due_at' => now()->addDay(),
            'summary' => 'Initial remarks',
        ]);

        $response = $this
            ->actingAs($user)
            ->patch(route('clinicFollowUpUpdate', $followUp), [
                'follow_up_method' => 'call',
                'stage' => 'contacted',
                'next_follow_up_due_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
                'remarks' => 'Updated remarks',
                'follow_up_id' => $followUp->id,
                'follow_up_edit_submission' => '1',
            ]);

        $response->assertForbidden();

        $followUp->refresh();

        $this->assertSame('manual_lead_create', $followUp->trigger_type);
        $this->assertSame('new', $followUp->stage_snapshot);
        $this->assertSame('Initial remarks', $followUp->summary);
    }

    public function test_soft_deleted_lead_is_hidden_from_follow_up_queue(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'module_access' => ['lead_management'],
            'module_permissions' => [
                'lead_management' => ['manage_followups'],
            ],
        ]);

        [$contact, $lead] = $this->createLead();

        FollowUp::query()->create([
            'lead_id' => $lead->id,
            'contact_id' => $contact->id,
            'trigger_type' => 'manual_lead_create',
            'stage_snapshot' => 'new',
            'status' => 'pending',
            'due_at' => now()->addDay(),
            'summary' => 'Queue item',
        ]);

        $lead->delete();

        $response = $this
            ->actingAs($user)
            ->get(route('clinicAppointments'));

        $response->assertOk();
        $response->assertDontSee($contact->full_name);
    }

    /**
     * @return array{0: Contact, 1: Lead}
     */
    private function createLead(): array
    {
        $contact = Contact::query()->create([
            'full_name' => 'Ayesha Khan',
            'gender' => 'female',
            'phone' => '+923001234567',
            'normalized_phone' => '+923001234567',
            'default_source' => 'manual',
        ]);

        $lead = Lead::query()->create([
            'contact_id' => $contact->id,
            'source_platform' => 'manual',
            'status' => 'open',
            'stage' => 'new',
            'last_activity_at' => now(),
            'meta' => [
                'origin' => 'manual_form',
            ],
        ]);

        return [$contact, $lead];
    }
}
