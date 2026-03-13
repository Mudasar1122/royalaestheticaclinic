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
}
