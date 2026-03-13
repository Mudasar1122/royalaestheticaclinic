<?php

namespace Tests\Feature;

use App\Models\Contact;
use App\Models\Lead;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavbarLeadSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_navbar_uses_lead_search_form_and_hides_language_and_message_controls(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'module_access' => ['lead_management'],
            'module_permissions' => [
                'lead_management' => ['view_leads', 'manage_followups'],
            ],
        ]);

        $response = $this
            ->actingAs($user)
            ->get(route('clinicLeads'));

        $response->assertOk();
        $response->assertSee('action="' . route('clinicLeads') . '"', false);
        $response->assertSee('placeholder="Search lead by name or phone"', false);
        $response->assertSee('name="tab" value="all"', false);
        $response->assertDontSee('dropdownInformation', false);
        $response->assertDontSee('dropdownMessage', false);
        $response->assertDontSee('lang-flag.png', false);
        $response->assertDontSee('mage:email', false);
    }

    public function test_leads_search_filters_by_name_and_phone_and_keeps_lead_actions(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'module_access' => ['lead_management'],
            'module_permissions' => [
                'lead_management' => ['view_leads', 'manage_followups', 'mark_booked'],
            ],
        ]);

        [$matchingContact, $matchingLead] = $this->createLead('Ayesha Khan', '+923001234567', $user);
        [$otherContact] = $this->createLead('Sana Malik', '+923009998887', $user);

        $nameResponse = $this
            ->actingAs($user)
            ->get(route('clinicLeads', [
                'tab' => 'all',
                'search' => 'Ayesha',
            ]));

        $nameResponse->assertOk();
        $nameResponse->assertSee($matchingContact->full_name);
        $nameResponse->assertDontSee($otherContact->full_name);
        $nameResponse->assertSee('Add Follow-up');
        $nameResponse->assertSee('Mark as Booked');
        $nameResponse->assertSee(route('clinicLeadFollowUp', $matchingLead), false);

        $phoneResponse = $this
            ->actingAs($user)
            ->get(route('clinicLeads', [
                'tab' => 'all',
                'search' => '+923001234567',
            ]));

        $phoneResponse->assertOk();
        $phoneResponse->assertSee($matchingContact->full_name);
        $phoneResponse->assertDontSee($otherContact->full_name);
        $phoneResponse->assertSee($matchingContact->phone);
        $phoneResponse->assertSee('value="+923001234567"', false);
    }

    public function test_staff_only_sees_assigned_leads_on_leads_page(): void
    {
        $user = User::factory()->create([
            'role' => 'staff',
            'module_access' => ['lead_management'],
            'module_permissions' => [
                'lead_management' => ['view_leads', 'manage_followups'],
            ],
        ]);
        $otherUser = User::factory()->create([
            'role' => 'staff',
        ]);

        [$ownContact] = $this->createLead('Assigned Lead', '+923451112233', $user);
        [$otherContact] = $this->createLead('Other Lead', '+923451112244', $otherUser);

        $response = $this
            ->actingAs($user)
            ->get(route('clinicLeads'));

        $response->assertOk();
        $response->assertSee($ownContact->full_name);
        $response->assertDontSee($otherContact->full_name);
    }

    /**
     * @return array{0: Contact, 1: Lead}
     */
    private function createLead(string $fullName, string $phone, ?User $assignedUser = null): array
    {
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
            'assigned_to_user_id' => $assignedUser?->id,
            'last_activity_at' => now(),
            'meta' => [
                'origin' => 'manual_form',
                'procedures_of_interest' => ['laser_hair_removal'],
            ],
        ]);

        return [$contact, $lead];
    }
}
