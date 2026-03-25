<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Database\Seeder;

class TestingLeadSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::query()->orderBy('id')->get();

        if ($users->isEmpty()) {
            $users = collect([
                User::factory()->create([
                    'name' => 'Seed Admin',
                    'email' => 'seed-admin@example.com',
                    'role' => 'admin',
                    'module_access' => ['lead_management', 'campaign_management'],
                    'module_permissions' => [
                        'lead_management' => [
                            'view_leads',
                            'view_all_leads',
                            'create_lead',
                            'edit_lead',
                            'manage_followups',
                            'mark_booked',
                            'send_whatsapp',
                        ],
                        'campaign_management' => [
                            'view_campaigns',
                            'send_email_campaign',
                            'send_whatsapp_campaign',
                        ],
                    ],
                ]),
            ]);
        }

        $now = now('Asia/Karachi');
        $fakeLeads = [
            [
                'full_name' => 'Ayesha Khan',
                'gender' => 'female',
                'email' => 'ayesha.khan.testing@example.com',
                'phone' => '+923001110001',
                'source_platform' => 'facebook',
                'stage' => 'new',
                'remarks' => 'Asked about laser hair removal package and wants a callback.',
                'procedures' => ['laser_hair_removal'],
                'follow_up_due_at' => $now->copy()->addHours(2),
                'follow_up_status' => 'pending',
            ],
            [
                'full_name' => 'Sana Malik',
                'gender' => 'female',
                'email' => 'sana.malik.testing@example.com',
                'phone' => '+923001110002',
                'source_platform' => 'instagram',
                'stage' => 'contacted',
                'remarks' => 'Interested in pigmentation treatment after seeing Instagram reel.',
                'procedures' => ['pigmentation_melasma_freckles'],
                'follow_up_due_at' => $now->copy()->addHours(4),
                'follow_up_status' => 'pending',
            ],
            [
                'full_name' => 'Hira Ahmed',
                'gender' => 'female',
                'email' => 'hira.ahmed.testing@example.com',
                'phone' => '+923001110003',
                'source_platform' => 'whatsapp',
                'stage' => 'negotiation',
                'remarks' => 'Comparing PRP and hair restoration packages with another clinic.',
                'procedures' => ['prp_face_hair', 'hair_restoration_hair_fall_treatment'],
                'follow_up_due_at' => $now->copy()->addHours(6),
                'follow_up_status' => 'pending',
            ],
            [
                'full_name' => 'Ali Raza',
                'gender' => 'male',
                'email' => 'ali.raza.testing@example.com',
                'phone' => '+923001110004',
                'source_platform' => 'tiktok',
                'stage' => 'visit',
                'remarks' => 'Visited clinic once and asked for acne scar options.',
                'procedures' => ['acne_acne_scars'],
                'follow_up_due_at' => $now->copy()->addHours(8),
                'follow_up_status' => 'pending',
            ],
            [
                'full_name' => 'Fatima Noor',
                'gender' => 'female',
                'email' => 'fatima.noor.testing@example.com',
                'phone' => '+923001110005',
                'source_platform' => 'google_business',
                'stage' => 'booked',
                'remarks' => 'Booked whitening session for the weekend.',
                'procedures' => ['skin_whitening_brightening'],
                'follow_up_due_at' => $now->copy()->addHours(10),
                'follow_up_status' => 'pending',
            ],
            [
                'full_name' => 'Usman Tariq',
                'gender' => 'male',
                'email' => 'usman.tariq.testing@example.com',
                'phone' => '+923001110006',
                'source_platform' => 'meta',
                'stage' => 'procedure_attempted',
                'remarks' => 'Completed consultation and proceeded with PRP trial session.',
                'procedures' => ['prp_face_hair'],
                'follow_up_due_at' => $now->copy()->addHours(12),
                'follow_up_status' => 'pending',
            ],
            [
                'full_name' => 'Maryam Javed',
                'gender' => 'female',
                'email' => 'maryam.javed.testing@example.com',
                'phone' => '+923001110007',
                'source_platform' => 'manual',
                'stage' => 'not_interested',
                'remarks' => 'Walk-in lead who decided not to continue after consultation.',
                'procedures' => ['botox_dermal_fillers'],
                'follow_up_due_at' => $now->copy()->addHours(14),
                'follow_up_status' => 'pending',
            ],
            [
                'full_name' => 'Zainab Sheikh',
                'gender' => 'female',
                'email' => 'zainab.sheikh.testing@example.com',
                'phone' => '+923001110008',
                'source_platform' => 'manual',
                'stage' => 'contacted',
                'remarks' => 'Asked for anti-aging treatment pricing during walk-in.',
                'procedures' => ['anti_aging_face_lifting'],
                'follow_up_due_at' => $now->copy()->addHours(16),
                'follow_up_status' => 'pending',
            ],
            [
                'full_name' => 'Hamza Saeed',
                'gender' => 'male',
                'email' => 'hamza.saeed.testing@example.com',
                'phone' => '+923001110009',
                'source_platform' => 'instagram',
                'stage' => 'negotiation',
                'remarks' => 'Considering body contouring if installment plan is available.',
                'procedures' => ['body_contouring_fat_reduction'],
                'follow_up_due_at' => $now->copy()->addHours(18),
                'follow_up_status' => 'pending',
            ],
            [
                'full_name' => 'Noor ul Ain',
                'gender' => 'female',
                'email' => 'noor.ulain.testing@example.com',
                'phone' => '+923001110010',
                'source_platform' => 'whatsapp',
                'stage' => 'new',
                'remarks' => 'Requested details for chemical peel and available slots.',
                'procedures' => ['chemical_peels_carbon_peel'],
                'follow_up_due_at' => $now->copy()->addHours(20),
                'follow_up_status' => 'pending',
            ],
        ];

        foreach ($fakeLeads as $index => $data) {
            $owner = $users[$index % $users->count()];
            $isClosed = in_array($data['stage'], ['booked', 'procedure_attempted', 'not_interested'], true);
            $followUpDueAtUtc = $data['follow_up_due_at']->copy()->utc();
            $lastActivityAtUtc = $followUpDueAtUtc->copy()->subHour();

            $contact = Contact::query()->updateOrCreate(
                ['normalized_phone' => $data['phone']],
                [
                    'full_name' => $data['full_name'],
                    'gender' => $data['gender'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'default_source' => $data['source_platform'],
                    'metadata' => [
                        'created_from' => 'testing_lead_seeder',
                    ],
                ]
            );

            $lead = Lead::query()->updateOrCreate(
                [
                    'contact_id' => $contact->id,
                    'source_platform' => $data['source_platform'],
                ],
                [
                    'status' => $isClosed ? 'closed' : 'open',
                    'stage' => $data['stage'],
                    'assigned_to_user_id' => $owner->id,
                    'first_message_at' => $lastActivityAtUtc->copy()->subHours(2),
                    'last_activity_at' => $lastActivityAtUtc,
                    'closed_at' => $isClosed ? $followUpDueAtUtc->copy()->addHour() : null,
                    'meta' => [
                        'origin' => 'testing_lead_seeder',
                        'procedures_of_interest' => $data['procedures'],
                    ],
                ]
            );

            LeadActivity::query()->updateOrCreate(
                [
                    'lead_id' => $lead->id,
                    'activity_type' => 'manual_note',
                ],
                [
                    'contact_id' => $contact->id,
                    'platform' => $data['source_platform'],
                    'direction' => 'inbound',
                    'message_text' => $data['remarks'],
                    'payload' => [
                        'source' => 'testing_lead_seeder',
                        'procedures_of_interest' => $data['procedures'],
                    ],
                    'happened_at' => $lastActivityAtUtc,
                    'created_by_user_id' => $owner->id,
                ]
            );

            FollowUp::query()->updateOrCreate(
                [
                    'lead_id' => $lead->id,
                    'trigger_type' => 'manual_lead_create',
                ],
                [
                    'contact_id' => $contact->id,
                    'stage_snapshot' => $data['stage'],
                    'status' => $data['follow_up_status'],
                    'due_at' => $followUpDueAtUtc,
                    'completed_at' => $data['follow_up_status'] === 'completed'
                        ? $followUpDueAtUtc->copy()->addHour()
                        : null,
                    'summary' => $data['remarks'],
                    'metadata' => [
                        'source' => 'testing_lead_seeder',
                        'platform' => $data['source_platform'],
                        'procedures_of_interest' => $data['procedures'],
                    ],
                    'assigned_to_user_id' => $owner->id,
                    'created_by_user_id' => $owner->id,
                ]
            );
        }

        $this->command?->info('Seeded 10 fake testing leads.');
    }
}
