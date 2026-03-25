<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\User;
use App\Models\WebhookEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavbarNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_follow_up_notification_links_to_lead_follow_up_page(): void
    {
        $user = User::factory()->create([
            'module_access' => ['lead_management'],
            'module_permissions' => [
                'lead_management' => ['manage_followups'],
            ],
        ]);

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
            'assigned_to_user_id' => $user->id,
            'last_activity_at' => now(),
            'meta' => [
                'origin' => 'manual_form',
            ],
        ]);

        FollowUp::query()->create([
            'lead_id' => $lead->id,
            'contact_id' => $contact->id,
            'trigger_type' => 'manual_lead_create',
            'stage_snapshot' => 'new',
            'status' => 'pending',
            'due_at' => now()->addHours(2),
            'summary' => 'Call back customer',
            'assigned_to_user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboardIndex'));

        $response->assertOk();
        $response->assertSee(route('clinicLeadFollowUp', $lead), false);
    }

    public function test_webhook_notification_with_lead_id_links_to_lead_follow_up_page(): void
    {
        $user = User::factory()->create([
            'module_access' => ['lead_management'],
            'module_permissions' => [
                'lead_management' => ['manage_followups'],
            ],
        ]);

        $contact = Contact::query()->create([
            'full_name' => 'Sarah Khan',
            'gender' => 'female',
            'phone' => '+923111112222',
            'normalized_phone' => '+923111112222',
            'default_source' => 'whatsapp',
        ]);

        $lead = Lead::query()->create([
            'contact_id' => $contact->id,
            'source_platform' => 'whatsapp',
            'status' => 'open',
            'stage' => 'contacted',
            'assigned_to_user_id' => $user->id,
            'last_activity_at' => now(),
            'meta' => [
                'origin' => 'whatsapp_webhook',
            ],
        ]);

        WebhookEvent::query()->create([
            'platform' => 'whatsapp',
            'event_id' => 'evt_123',
            'event_type' => 'inbound_message',
            'payload' => [
                'Body' => 'Hello',
                'From' => '923111112222',
                '_crm' => [
                    'lead_id' => $lead->id,
                    'manual_follow_up_required' => true,
                ],
            ],
            'status' => 'failed',
            'error_message' => 'Automatic follow-up failed. Please add it manually.',
            'received_at' => now()->subHour(),
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboardIndex'));

        $response->assertOk();
        $response->assertSee(route('clinicLeadFollowUp', $lead), false);
    }

    public function test_notification_page_rows_link_to_lead_follow_up_page(): void
    {
        $user = User::factory()->create([
            'module_access' => ['lead_management'],
            'module_permissions' => [
                'lead_management' => ['manage_followups'],
            ],
        ]);

        $contact = Contact::query()->create([
            'full_name' => 'Hina Malik',
            'gender' => 'female',
            'phone' => '+923451112233',
            'normalized_phone' => '+923451112233',
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
            ],
        ]);

        FollowUp::query()->create([
            'lead_id' => $lead->id,
            'contact_id' => $contact->id,
            'trigger_type' => 'manual_lead_create',
            'stage_snapshot' => 'new',
            'status' => 'pending',
            'due_at' => now()->addHours(2),
            'summary' => 'Add follow-up from notification page',
            'assigned_to_user_id' => $user->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('notification'));

        $response->assertOk();
        $response->assertSee('data-notification-url="'.route('clinicLeadFollowUp', $lead).'"', false);
    }

    public function test_staff_does_not_see_notifications_for_other_users_leads(): void
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

        $contact = Contact::query()->create([
            'full_name' => 'Hidden Lead',
            'gender' => 'female',
            'phone' => '+923221112233',
            'normalized_phone' => '+923221112233',
            'default_source' => 'manual',
        ]);

        $lead = Lead::query()->create([
            'contact_id' => $contact->id,
            'source_platform' => 'manual',
            'status' => 'open',
            'stage' => 'new',
            'assigned_to_user_id' => $otherUser->id,
            'last_activity_at' => now(),
            'meta' => [
                'origin' => 'manual_form',
            ],
        ]);

        FollowUp::query()->create([
            'lead_id' => $lead->id,
            'contact_id' => $contact->id,
            'trigger_type' => 'manual_lead_create',
            'stage_snapshot' => 'new',
            'status' => 'pending',
            'due_at' => now()->addHours(1),
            'summary' => 'Private follow-up',
            'assigned_to_user_id' => $otherUser->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('dashboardIndex'));

        $response->assertOk();
        $response->assertDontSee(route('clinicLeadFollowUp', $lead), false);
        $response->assertDontSee('Private follow-up');
    }

    public function test_navbar_dropdown_initially_renders_four_notifications_and_keeps_total_count(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'module_access' => ['lead_management'],
            'module_permissions' => [
                'lead_management' => ['manage_followups'],
            ],
        ]);

        foreach (range(1, 6) as $index) {
            $this->createFollowUpNotificationLead($user, 'Notification Lead '.$index, $index);
        }

        $response = $this
            ->actingAs($user)
            ->get(route('dashboardIndex'));

        $response->assertOk();
        $response->assertSee('data-total-count="6"', false);
        $response->assertSee('data-has-more="1"', false);
        $this->assertSame(4, substr_count($response->getContent(), 'data-navbar-notification-item'));
    }

    public function test_navbar_notification_feed_returns_next_page_of_notifications(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'module_access' => ['lead_management'],
            'module_permissions' => [
                'lead_management' => ['manage_followups'],
            ],
        ]);

        foreach (range(1, 6) as $index) {
            $this->createFollowUpNotificationLead($user, 'Paged Notification Lead '.$index, $index);
        }

        $response = $this
            ->actingAs($user)
            ->getJson(route('notificationFeed', [
                'page' => 2,
                'per_page' => 4,
            ]));

        $response->assertOk();
        $response->assertJson([
            'current_page' => 2,
            'per_page' => 4,
            'has_more' => false,
            'next_page' => null,
            'total_count' => 6,
            'highlighted_count' => 6,
        ]);
        $response->assertSee('Paged Notification Lead 2');
        $response->assertSee('Paged Notification Lead 1');
        $response->assertDontSee('Paged Notification Lead 5');
    }

    public function test_admin_sees_notifications_for_all_leads(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
        ]);
        $owner = User::factory()->create([
            'role' => 'staff',
        ]);

        $contact = Contact::query()->create([
            'full_name' => 'Clinic Lead',
            'gender' => 'female',
            'phone' => '+923331112233',
            'normalized_phone' => '+923331112233',
            'default_source' => 'manual',
        ]);

        $lead = Lead::query()->create([
            'contact_id' => $contact->id,
            'source_platform' => 'manual',
            'status' => 'open',
            'stage' => 'contacted',
            'assigned_to_user_id' => $owner->id,
            'last_activity_at' => now(),
            'meta' => [
                'origin' => 'manual_form',
            ],
        ]);

        FollowUp::query()->create([
            'lead_id' => $lead->id,
            'contact_id' => $contact->id,
            'trigger_type' => 'manual_lead_create',
            'stage_snapshot' => 'contacted',
            'status' => 'pending',
            'due_at' => now()->addHours(2),
            'summary' => 'Admin can see this',
            'assigned_to_user_id' => $owner->id,
        ]);

        $response = $this
            ->actingAs($admin)
            ->get(route('dashboardIndex'));

        $response->assertOk();
        $response->assertSee(route('clinicLeadFollowUp', $lead), false);
        $response->assertSee('Admin can see this');
    }

    private function createFollowUpNotificationLead(User $user, string $fullName, int $dueInHours): Lead
    {
        $phone = '+92300'.str_pad((string) $dueInHours, 7, '0', STR_PAD_LEFT);
        $contact = Contact::query()->create([
            'full_name' => $fullName,
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
            ],
        ]);

        FollowUp::query()->create([
            'lead_id' => $lead->id,
            'contact_id' => $contact->id,
            'trigger_type' => 'manual_lead_create',
            'stage_snapshot' => 'new',
            'status' => 'pending',
            'due_at' => now()->addHours($dueInHours),
            'summary' => 'Notification follow-up for '.$fullName,
            'assigned_to_user_id' => $user->id,
        ]);

        return $lead;
    }
}
