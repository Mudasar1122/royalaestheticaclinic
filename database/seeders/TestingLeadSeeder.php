<?php

namespace Database\Seeders;

use App\Models\Contact;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

class TestingLeadSeeder extends Seeder
{
    public function run(): void
    {
        $users = $this->resolveSeedUsers();

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
                'last_activity_at' => $now->copy()->subHours(6),
                'follow_ups' => [
                    [
                        'trigger_type' => 'manual_lead_create',
                        'stage_snapshot' => 'new',
                        'status' => 'pending',
                        'due_at' => $now->copy()->subHours(2),
                        'completed_at' => null,
                        'summary' => 'Call back with laser package pricing and ongoing offers.',
                    ],
                ],
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
                'last_activity_at' => $now->copy()->subHours(4),
                'follow_ups' => [
                    [
                        'trigger_type' => 'manual_lead_create',
                        'stage_snapshot' => 'new',
                        'status' => 'completed',
                        'due_at' => $now->copy()->subDays(2),
                        'completed_at' => $now->copy()->subDays(2)->addHours(1),
                        'summary' => 'Sent reel pricing summary and shared before-and-after examples.',
                    ],
                    [
                        'trigger_type' => 'call',
                        'stage_snapshot' => 'contacted',
                        'status' => 'pending',
                        'due_at' => $now->copy()->addHours(3),
                        'completed_at' => null,
                        'summary' => 'Confirm consultation slot for pigmentation treatment.',
                    ],
                ],
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
                'last_activity_at' => $now->copy()->subHours(3),
                'follow_ups' => [
                    [
                        'trigger_type' => 'whatsapp',
                        'stage_snapshot' => 'contacted',
                        'status' => 'completed',
                        'due_at' => $now->copy()->subDays(1)->subHours(4),
                        'completed_at' => $now->copy()->subDays(1)->subHours(2),
                        'summary' => 'Shared PRP package details and explained session count.',
                    ],
                    [
                        'trigger_type' => 'call',
                        'stage_snapshot' => 'negotiation',
                        'status' => 'pending',
                        'due_at' => $now->copy()->addDay()->addHours(2),
                        'completed_at' => null,
                        'summary' => 'Follow up after competitor quote review.',
                    ],
                ],
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
                'last_activity_at' => $now->copy()->subHours(5),
                'follow_ups' => [
                    [
                        'trigger_type' => 'walkin',
                        'stage_snapshot' => 'visit',
                        'status' => 'completed',
                        'due_at' => $now->copy()->subDay(),
                        'completed_at' => $now->copy()->subDay()->addMinutes(45),
                        'summary' => 'Clinic visit completed and acne scar treatment plan discussed.',
                    ],
                    [
                        'trigger_type' => 'manual_stage_update',
                        'stage_snapshot' => 'visit',
                        'status' => 'pending',
                        'due_at' => $now->copy()->addHours(5),
                        'completed_at' => null,
                        'summary' => 'Check if he wants to start with fractional resurfacing.',
                    ],
                ],
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
                'last_activity_at' => $now->copy()->subHours(10),
                'closed_at' => $now->copy()->subHours(10),
                'follow_ups' => [
                    [
                        'trigger_type' => 'manual_stage_update',
                        'stage_snapshot' => 'booked',
                        'status' => 'completed',
                        'due_at' => $now->copy()->subDays(2)->subHours(3),
                        'completed_at' => $now->copy()->subDays(2)->subHours(2),
                        'summary' => 'Booking deposit confirmed and whitening prep instructions sent.',
                    ],
                    [
                        'trigger_type' => 'call',
                        'stage_snapshot' => 'booked',
                        'status' => 'pending',
                        'due_at' => $now->copy()->addDay()->addHours(4),
                        'completed_at' => null,
                        'summary' => 'Reminder call one day before whitening appointment.',
                    ],
                ],
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
                'last_activity_at' => $now->copy()->subDay(),
                'closed_at' => $now->copy()->subDay(),
                'follow_ups' => [
                    [
                        'trigger_type' => 'manual_stage_update',
                        'stage_snapshot' => 'booked',
                        'status' => 'completed',
                        'due_at' => $now->copy()->subDays(2)->subHours(5),
                        'completed_at' => $now->copy()->subDays(2)->subHours(4),
                        'summary' => 'Booking confirmed for PRP trial session.',
                    ],
                    [
                        'trigger_type' => 'sms',
                        'stage_snapshot' => 'procedure_attempted',
                        'status' => 'completed',
                        'due_at' => $now->copy()->subDay(),
                        'completed_at' => $now->copy()->subDay()->addMinutes(20),
                        'summary' => 'Post-procedure care instructions sent by SMS.',
                    ],
                ],
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
                'last_activity_at' => $now->copy()->subDays(3),
                'closed_at' => $now->copy()->subDays(3),
                'follow_ups' => [
                    [
                        'trigger_type' => 'manual_stage_update',
                        'stage_snapshot' => 'not_interested',
                        'status' => 'completed',
                        'due_at' => $now->copy()->subDays(3),
                        'completed_at' => $now->copy()->subDays(3)->addMinutes(30),
                        'summary' => 'Recorded that the lead declined after cost discussion.',
                    ],
                ],
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
                'last_activity_at' => $now->copy()->subHours(2),
                'follow_ups' => [
                    [
                        'trigger_type' => 'manual_lead_create',
                        'stage_snapshot' => 'new',
                        'status' => 'completed',
                        'due_at' => $now->copy()->subDays(2)->subHours(1),
                        'completed_at' => $now->copy()->subDays(2),
                        'summary' => 'Walk-in details captured and package brochure shared.',
                    ],
                    [
                        'trigger_type' => 'whatsapp',
                        'stage_snapshot' => 'contacted',
                        'status' => 'pending',
                        'due_at' => $now->copy()->addHours(8),
                        'completed_at' => null,
                        'summary' => 'Send anti-aging treatment prices and before-after photos.',
                    ],
                ],
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
                'last_activity_at' => $now->copy()->subHour(),
                'follow_ups' => [
                    [
                        'trigger_type' => 'manual_lead_create',
                        'stage_snapshot' => 'new',
                        'status' => 'completed',
                        'due_at' => $now->copy()->subDays(5),
                        'completed_at' => $now->copy()->subDays(5)->addHours(1),
                        'summary' => 'Initial inquiry captured from Instagram DM.',
                    ],
                    [
                        'trigger_type' => 'call',
                        'stage_snapshot' => 'contacted',
                        'status' => 'completed',
                        'due_at' => $now->copy()->subDays(3)->subHours(2),
                        'completed_at' => $now->copy()->subDays(3)->subHours(1),
                        'summary' => 'Explained installment plan ranges and recovery time.',
                    ],
                    [
                        'trigger_type' => 'whatsapp',
                        'stage_snapshot' => 'negotiation',
                        'status' => 'pending',
                        'due_at' => $now->copy()->subHour(),
                        'completed_at' => null,
                        'summary' => 'Send revised installment plan and schedule callback.',
                    ],
                ],
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
                'last_activity_at' => $now->copy()->subHours(7),
                'follow_ups' => [
                    [
                        'trigger_type' => 'manual_lead_create',
                        'stage_snapshot' => 'new',
                        'status' => 'pending',
                        'due_at' => $now->copy()->addDays(2),
                        'completed_at' => null,
                        'summary' => 'Reply with chemical peel pricing and available evening slots.',
                    ],
                ],
            ],
            [
                'full_name' => 'Bilal Hassan',
                'gender' => 'male',
                'email' => 'bilal.hassan.testing@example.com',
                'phone' => '+923001110011',
                'source_platform' => 'facebook',
                'stage' => 'booked',
                'remarks' => 'Booked cosmetic consultation after comparing packages.',
                'procedures' => ['cosmetic_surgical_consultation'],
                'last_activity_at' => $now->copy()->subHours(8),
                'closed_at' => $now->copy()->subHours(8),
                'follow_ups' => [
                    [
                        'trigger_type' => 'manual_lead_create',
                        'stage_snapshot' => 'contacted',
                        'status' => 'completed',
                        'due_at' => $now->copy()->subDays(4),
                        'completed_at' => $now->copy()->subDays(4)->addHours(2),
                        'summary' => 'Shared consultation pricing and surgeon availability.',
                    ],
                    [
                        'trigger_type' => 'manual_stage_update',
                        'stage_snapshot' => 'booked',
                        'status' => 'completed',
                        'due_at' => $now->copy()->subDays(2),
                        'completed_at' => $now->copy()->subDays(2)->addMinutes(30),
                        'summary' => 'Consultation booking confirmed with advance payment.',
                    ],
                    [
                        'trigger_type' => 'call',
                        'stage_snapshot' => 'booked',
                        'status' => 'pending',
                        'due_at' => $now->copy()->addHours(6),
                        'completed_at' => null,
                        'summary' => 'Reminder call before cosmetic consultation.',
                    ],
                ],
            ],
            [
                'full_name' => 'Iqra Faisal',
                'gender' => 'female',
                'email' => 'iqra.faisal.testing@example.com',
                'phone' => '+923001110012',
                'source_platform' => 'google_business',
                'stage' => 'visit',
                'remarks' => 'Visited for skin tightening options and wants a second opinion.',
                'procedures' => ['skin_tightening_hifu_rf'],
                'last_activity_at' => $now->copy()->subHours(12),
                'follow_ups' => [
                    [
                        'trigger_type' => 'sms',
                        'stage_snapshot' => 'contacted',
                        'status' => 'completed',
                        'due_at' => $now->copy()->subDay()->subHours(3),
                        'completed_at' => $now->copy()->subDay()->subHours(2),
                        'summary' => 'Sent HIFU and RF comparison after inquiry.',
                    ],
                    [
                        'trigger_type' => 'walkin',
                        'stage_snapshot' => 'visit',
                        'status' => 'pending',
                        'due_at' => $now->copy()->addDay()->addHours(6),
                        'completed_at' => null,
                        'summary' => 'Follow up after visit and confirm preferred skin tightening plan.',
                    ],
                ],
            ],
        ];

        foreach ($fakeLeads as $index => $data) {
            $owner = $users[$index % $users->count()];
            $isClosed = $this->isClosedStage($data['stage']);
            $lastActivityAtUtc = $data['last_activity_at']->copy()->utc();
            $firstMessageAtUtc = $data['last_activity_at']->copy()->subDay()->utc();
            $closedAtUtc = $isClosed
                ? ($data['closed_at'] ?? $data['last_activity_at'])->copy()->utc()
                : null;

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
                    'first_message_at' => $firstMessageAtUtc,
                    'last_activity_at' => $lastActivityAtUtc,
                    'closed_at' => $closedAtUtc,
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

            foreach ($data['follow_ups'] as $followUp) {
                FollowUp::query()->updateOrCreate(
                    [
                        'lead_id' => $lead->id,
                        'trigger_type' => $followUp['trigger_type'],
                        'summary' => $followUp['summary'],
                    ],
                    [
                        'contact_id' => $contact->id,
                        'stage_snapshot' => $followUp['stage_snapshot'],
                        'status' => $followUp['status'],
                        'due_at' => $followUp['due_at']->copy()->utc(),
                        'completed_at' => $followUp['completed_at']?->copy()->utc(),
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
        }

        $this->command?->info('Seeded '.count($fakeLeads).' CRM testing leads with mixed stages and follow-up history.');
    }

    /**
     * @return Collection<int, User>
     */
    private function resolveSeedUsers(): Collection
    {
        $crmUsers = User::query()
            ->get()
            ->filter(function (User $user): bool {
                if ($user->isAdmin()) {
                    return true;
                }

                $moduleAccess = is_array($user->module_access) ? $user->module_access : [];

                return in_array('lead_management', $moduleAccess, true);
            })
            ->values();

        if ($crmUsers->isNotEmpty()) {
            return $crmUsers;
        }

        return collect([
            User::query()->firstOrCreate(
                ['email' => 'seed-admin@example.com'],
                [
                    'name' => 'Seed Admin',
                    'password' => 'password',
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
                    'is_active' => true,
                ]
            ),
        ]);
    }

    private function isClosedStage(string $stage): bool
    {
        return in_array($stage, ['booked', 'procedure_attempted', 'not_interested'], true);
    }
}
