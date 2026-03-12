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
}
