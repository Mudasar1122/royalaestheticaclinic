<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClinicManualLeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_lead_form_defaults_gender_to_female(): void
    {
        $user = User::factory()->create([
            'module_access' => ['lead_management'],
            'module_permissions' => [
                'lead_management' => ['create_lead', 'manage_followups'],
            ],
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('clinicManualLead'));

        $response->assertOk();
        $response->assertSee('type="radio"', false);
        $response->assertSee('name="gender"', false);
        $response->assertSee('value="male"', false);
        $response->assertSeeInOrder(['value="female"', 'checked', 'Female'], false);
        $response->assertSee('Female', false);
        $response->assertSee('Male', false);
        $response->assertSee('btn-cancel', false);
    }

    public function test_manual_lead_store_saves_gender_on_contact(): void
    {
        $expectedFollowUpDueAt = now('Asia/Karachi')->addDay()->format('Y-m-d\TH:i');

        $user = User::factory()->create([
            'module_access' => ['lead_management'],
            'module_permissions' => [
                'lead_management' => ['create_lead', 'manage_followups'],
            ],
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('clinicManualLeadStore'), [
                'full_name' => 'Ayesha Khan',
                'gender' => 'female',
                'phone' => '+923001234567',
                'email' => 'ayesha@example.com',
                'source_platform' => 'manual',
                'stage' => 'new',
                'remarks' => 'Interested in laser hair removal.',
                'follow_up_due_at' => $expectedFollowUpDueAt,
                'procedure_interests' => ['laser_hair_removal'],
            ]);

        $response->assertRedirect(route('clinicAppointments'));
        $response->assertSessionHas('status');

        $this->assertDatabaseHas('contacts', [
            'full_name' => 'Ayesha Khan',
            'gender' => 'female',
            'phone' => '+923001234567',
            'email' => 'ayesha@example.com',
        ]);

        $contact = Contact::query()
            ->where('normalized_phone', '+923001234567')
            ->first();

        $this->assertNotNull($contact);
        $this->assertDatabaseHas('leads', [
            'contact_id' => $contact?->id,
            'source_platform' => 'manual',
            'stage' => 'new',
            'status' => 'open',
            'assigned_to_user_id' => $user->id,
        ]);
        $followUp = \App\Models\FollowUp::query()->where('contact_id', $contact?->id)->first();
        $this->assertNotNull($followUp);
        $this->assertSame(
            str_replace('T', ' ', $expectedFollowUpDueAt),
            $followUp?->due_at?->timezone('Asia/Karachi')->format('Y-m-d H:i')
        );
        $this->assertSame('female', $contact?->gender);
        $this->assertSame(1, Lead::query()->count());
    }

    public function test_duplicate_phone_error_includes_existing_lead_name(): void
    {
        $user = User::factory()->create([
            'module_access' => ['lead_management'],
            'module_permissions' => [
                'lead_management' => ['create_lead'],
            ],
        ]);

        $contact = Contact::query()->create([
            'full_name' => 'Existing Lead',
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

        $response = $this
            ->actingAs($user)
            ->from(route('clinicManualLead'))
            ->followingRedirects()
            ->post(route('clinicManualLeadStore'), [
                'full_name' => 'Another Person',
                'gender' => 'female',
                'phone' => '+923001234567',
                'email' => 'another@example.com',
                'source_platform' => 'manual',
                'stage' => 'new',
                'remarks' => 'Duplicate test',
                'follow_up_due_at' => now('Asia/Karachi')->addDay()->format('Y-m-d\TH:i'),
            ]);

        $response->assertOk();
        $response->assertSee('This phone number is already registered for Existing Lead.');
    }

    public function test_leads_page_shows_phone_number_only_in_phone_column(): void
    {
        $user = User::factory()->create([
            'module_access' => ['lead_management'],
            'module_permissions' => [
                'lead_management' => ['view_leads'],
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

        $response = $this
            ->actingAs($user)
            ->get(route('clinicLeads'));

        $response->assertOk();
        $response->assertSeeInOrder(['Phone No', 'Source', 'Created At'], false);
        $response->assertSee('+923001234567');
        $response->assertDontSee('+923001234567 / Female');
        $response->assertSee('Walk In Lead');
        $response->assertSee($lead->fresh()?->created_at?->timezone('Asia/Karachi')->format('d M Y h:i A').' PKT');
    }
}
