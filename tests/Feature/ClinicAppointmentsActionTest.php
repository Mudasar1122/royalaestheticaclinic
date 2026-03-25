<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class ClinicAppointmentsActionTest extends TestCase
{
    use RefreshDatabase;

    public function test_appointments_action_menu_shows_add_follow_up_without_mark_booked_permission(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'module_access' => ['lead_management'],
            'module_permissions' => [
                'lead_management' => ['manage_followups'],
            ],
        ]);

        [$contact, $lead] = $this->createLeadWithPendingFollowUp($user);

        $response = $this
            ->actingAs($user)
            ->get(route('clinicAppointments', ['tab' => 'today']));

        $response->assertOk();
        $response->assertSee($contact->full_name);
        $response->assertSee($contact->phone);
        $response->assertDontSee($contact->phone . ' / Female');
        $response->assertSee('Next Follow Date');
        $response->assertSee('User');
        $response->assertSee($user->name);
        $response->assertSee('Action');
        $response->assertSee('Add Follow-up');
        $response->assertSee(route('clinicLeadFollowUp', $lead), false);
        $response->assertDontSee('Mark as Booked');
        $followUp = FollowUp::query()->where('lead_id', $lead->id)->first();

        $this->assertNotNull($followUp);
        $response->assertSee($followUp->due_at?->timezone('Asia/Karachi')->format('d M Y'));
        $response->assertSee($followUp->due_at?->timezone('Asia/Karachi')->format('h:i A').' PKT');
    }

    public function test_appointments_action_menu_also_shows_mark_as_booked_for_authorized_user(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'module_access' => ['lead_management'],
            'module_permissions' => [
                'lead_management' => ['manage_followups', 'mark_booked'],
            ],
        ]);

        [, $lead] = $this->createLeadWithPendingFollowUp($user);

        $response = $this
            ->actingAs($user)
            ->get(route('clinicAppointments', ['tab' => 'today']));

        $response->assertOk();
        $response->assertSee('Add Follow-up');
        $response->assertSee('Mark as Booked');
        $response->assertSee(route('clinicLeadStageUpdate', $lead), false);
    }

    public function test_appointments_only_show_leads_assigned_to_the_logged_in_user(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'module_access' => ['lead_management'],
            'module_permissions' => [
                'lead_management' => ['manage_followups'],
            ],
        ]);
        $otherUser = User::factory()->create([
            'role' => 'staff',
        ]);

        [$ownContact] = $this->createLeadWithPendingFollowUp($user, '+923001234567');
        [$otherContact] = $this->createLeadWithPendingFollowUp($otherUser, '+923009998887');

        $response = $this
            ->actingAs($user)
            ->get(route('clinicAppointments', ['tab' => 'today']));

        $response->assertOk();
        $response->assertSee($ownContact->phone);
        $response->assertDontSee($otherContact->phone);
    }

    public function test_appointment_counts_exclude_deleted_leads_for_admin(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 3, 25, 9, 0, 0, 'Asia/Karachi')->utc());

        try {
            $admin = User::factory()->create([
                'role' => 'admin',
            ]);

            [$todayContact] = $this->createLeadWithPendingFollowUp(
                $admin,
                '+923001110101',
                now('Asia/Karachi')->startOfDay()->addHours(12)->utc()
            );
            [$deletedTodayContact, $deletedTodayLead] = $this->createLeadWithPendingFollowUp(
                $admin,
                '+923001110102',
                now('Asia/Karachi')->startOfDay()->addHours(14)->utc()
            );
            [$pendingContact] = $this->createLeadWithPendingFollowUp(
                $admin,
                '+923001110103',
                now('Asia/Karachi')->subDay()->setTime(18, 0)->utc()
            );
            [$deletedPendingContact, $deletedPendingLead] = $this->createLeadWithPendingFollowUp(
                $admin,
                '+923001110104',
                now('Asia/Karachi')->subDay()->setTime(16, 0)->utc()
            );

            $deletedTodayLead->delete();
            $deletedPendingLead->delete();

            $todayResponse = $this
                ->actingAs($admin)
                ->get(route('clinicAppointments', ['tab' => 'today']));

            $todayResponse->assertOk();
            $todayResponse->assertViewHas('todayCount', 1);
            $todayResponse->assertViewHas('pendingCount', 1);
            $todayResponse->assertSee($todayContact->phone);
            $todayResponse->assertDontSee($deletedTodayContact->phone);

            $pendingResponse = $this
                ->actingAs($admin)
                ->get(route('clinicAppointments', ['tab' => 'pending']));

            $pendingResponse->assertOk();
            $pendingResponse->assertViewHas('todayCount', 1);
            $pendingResponse->assertViewHas('pendingCount', 1);
            $pendingResponse->assertSee($pendingContact->phone);
            $pendingResponse->assertDontSee($deletedPendingContact->phone);
        } finally {
            Carbon::setTestNow();
        }
    }

    /**
     * @return array{0: Contact, 1: Lead}
     */
    private function createLeadWithPendingFollowUp(
        User $user,
        string $phone = '+923001234567',
        $dueAt = null
    ): array
    {
        $contact = Contact::query()->create([
            'full_name' => 'Ayesha Khan',
            'gender' => 'female',
            'phone' => $phone,
            'normalized_phone' => $phone,
            'default_source' => 'manual',
        ]);

        $lead = Lead::query()->create([
            'contact_id' => $contact->id,
            'source_platform' => 'manual',
            'status' => 'open',
            'stage' => 'new',
            'assigned_to_user_id' => $user->id,
            'last_activity_at' => now(),
            'meta' => [
                'origin' => 'manual_form',
                'procedures_of_interest' => ['laser_hair_removal'],
            ],
        ]);

        FollowUp::query()->create([
            'lead_id' => $lead->id,
            'contact_id' => $contact->id,
            'trigger_type' => 'manual_lead_create',
            'stage_snapshot' => 'new',
            'status' => 'pending',
            'due_at' => $dueAt ?? now('Asia/Karachi')->startOfDay()->addHours(12)->utc(),
            'summary' => 'Queue item',
            'assigned_to_user_id' => $user->id,
            'created_by_user_id' => $user->id,
        ]);

        return [$contact, $lead];
    }
}
