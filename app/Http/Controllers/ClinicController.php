<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ClinicController extends Controller
{
    public function leads(Request $request): View
    {
        $leadTabs = $this->leadTabs();

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'tab' => ['nullable', 'string', 'max:30'],
            'source' => ['nullable', 'string', 'max:40'],
            'status' => ['nullable', 'string', 'max:20'],
        ]);

        $search = trim((string) ($validated['search'] ?? ''));
        $activeTab = (string) ($validated['tab'] ?? 'all');
        $sourceFilter = (string) ($validated['source'] ?? '');
        $statusFilter = (string) ($validated['status'] ?? '');

        if (!array_key_exists($activeTab, $leadTabs)) {
            $activeTab = 'all';
        }

        $tabStages = $leadTabs[$activeTab]['stages'] ?? [];

        $leadsQuery = Lead::query()
            ->with(['contact', 'assignedTo'])
            ->with(['followUps' => function ($query): void {
                $query->where('status', 'pending')->orderBy('due_at');
            }])
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->whereHas('contact', function (Builder $contactQuery) use ($search): void {
                            $contactQuery
                                ->where('full_name', 'like', '%'.$search.'%')
                                ->orWhere('phone', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        })
                        ->orWhere('source_platform', 'like', '%'.$search.'%')
                        ->orWhere('stage', 'like', '%'.$search.'%');
                });
            })
            ->when(!empty($tabStages), fn (Builder $query): Builder => $query->whereIn('stage', $tabStages))
            ->when($sourceFilter !== '', fn (Builder $query): Builder => $query->where('source_platform', $sourceFilter))
            ->when($statusFilter !== '', fn (Builder $query): Builder => $query->where('status', $statusFilter))
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->orderByDesc('last_activity_at');

        $leads = $leadsQuery->paginate(12)->withQueryString();

        $tabCounts = [];
        foreach ($leadTabs as $tabKey => $tabConfig) {
            $tabCounts[$tabKey] = Lead::query()
                ->when(
                    !empty($tabConfig['stages']),
                    fn (Builder $query): Builder => $query->whereIn('stage', $tabConfig['stages'])
                )
                ->count();
        }

        return view('clinic.leads', [
            'leads' => $leads,
            'stages' => $this->stageOptions(),
            'sources' => $this->sourceOptions(),
            'leadTabs' => $leadTabs,
            'activeTab' => $activeTab,
            'tabCounts' => $tabCounts,
            'filters' => [
                'search' => $search,
                'tab' => $activeTab,
                'source' => $sourceFilter,
                'status' => $statusFilter,
            ],
        ]);
    }

    public function createManualLead(): View
    {
        return view('clinic.manualLead', [
            'sources' => $this->sourceOptions(),
            'stages' => $this->stageOptions(),
        ]);
    }

    public function storeManualLead(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'source_platform' => ['required', 'string', 'in:facebook,instagram,whatsapp,tiktok,google_business,manual'],
            'stage' => ['required', 'string', 'in:new,initial,contacted,visit,negotiation,proposal,booked,confirmed'],
            'note' => ['nullable', 'string', 'max:2000'],
            'follow_up_due_at' => ['nullable', 'date'],
        ]);

        $normalizedStage = $this->normalizeLeadStage($validated['stage']);

        if (empty($validated['email']) && empty($validated['phone'])) {
            return back()
                ->withErrors(['phone' => 'Please provide at least email or phone.'])
                ->withInput();
        }

        DB::transaction(function () use ($validated): void {
            $normalizedPhone = $this->normalizePhone((string) ($validated['phone'] ?? ''));

            $contact = null;
            $email = $validated['email'] ?? null;

            if ($normalizedPhone !== null || !empty($email)) {
                $contact = Contact::query()
                    ->where(function (Builder $query) use ($normalizedPhone, $email): void {
                        if ($normalizedPhone !== null) {
                            $query->where('normalized_phone', $normalizedPhone);
                        }

                        if (!empty($email)) {
                            if ($normalizedPhone !== null) {
                                $query->orWhere('email', $email);
                            } else {
                                $query->where('email', $email);
                            }
                        }
                    })
                    ->first();
            }

            if ($contact === null) {
                $contact = Contact::create([
                    'full_name' => $validated['full_name'],
                    'email' => $validated['email'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'normalized_phone' => $normalizedPhone,
                    'default_source' => $validated['source_platform'],
                    'metadata' => [
                        'created_from' => 'manual_form',
                    ],
                ]);
            } else {
                $contact->forceFill([
                    'full_name' => $contact->full_name ?: $validated['full_name'],
                    'email' => $contact->email ?: ($validated['email'] ?? null),
                    'phone' => $contact->phone ?: ($validated['phone'] ?? null),
                    'normalized_phone' => $contact->normalized_phone ?: $normalizedPhone,
                ])->save();
            }

            $lead = Lead::create([
                'contact_id' => $contact->id,
                'source_platform' => $validated['source_platform'],
                'status' => $normalizedStage === 'booked' ? 'closed' : 'open',
                'stage' => $normalizedStage,
                'assigned_to_user_id' => auth()->id(),
                'first_message_at' => now(),
                'last_activity_at' => now(),
                'closed_at' => $normalizedStage === 'booked' ? now() : null,
                'meta' => [
                    'origin' => 'manual_form',
                ],
            ]);

            if (!empty($validated['note'])) {
                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'contact_id' => $contact->id,
                    'platform' => $validated['source_platform'],
                    'activity_type' => 'manual_note',
                    'direction' => 'inbound',
                    'message_text' => $validated['note'],
                    'payload' => [
                        'source' => 'manual_form',
                    ],
                    'happened_at' => now(),
                    'created_by_user_id' => auth()->id(),
                ]);
            }

            FollowUp::create([
                'lead_id' => $lead->id,
                'contact_id' => $contact->id,
                'trigger_type' => 'manual_lead_create',
                'stage_snapshot' => $normalizedStage,
                'status' => 'pending',
                'due_at' => !empty($validated['follow_up_due_at'])
                    ? $validated['follow_up_due_at']
                    : now()->addMinutes((int) config('crm.whatsapp.follow_up_minutes', 60)),
                'summary' => 'First follow-up generated from manual lead creation',
                'metadata' => [
                    'platform' => $validated['source_platform'],
                ],
                'assigned_to_user_id' => auth()->id(),
                'created_by_user_id' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('clinicLeads')
            ->with('status', 'Manual lead created and first follow-up scheduled.');
    }

    public function updateLeadStage(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'stage' => ['required', 'string', 'in:new,initial,contacted,visit,negotiation,proposal,booked,confirmed'],
            'follow_up_due_at' => ['nullable', 'date'],
            'follow_up_summary' => ['nullable', 'string', 'max:255'],
        ]);

        $newStage = $this->normalizeLeadStage($validated['stage']);
        $isClosed = $newStage === 'booked';

        DB::transaction(function () use ($lead, $validated, $newStage, $isClosed): void {
            $lead->forceFill([
                'stage' => $newStage,
                'status' => $isClosed ? 'closed' : 'open',
                'closed_at' => $isClosed ? now() : null,
                'last_activity_at' => now(),
            ])->save();

            if (!empty($validated['follow_up_due_at']) || !empty($validated['follow_up_summary'])) {
                FollowUp::create([
                    'lead_id' => $lead->id,
                    'contact_id' => $lead->contact_id,
                    'trigger_type' => 'manual_stage_update',
                    'stage_snapshot' => $lead->stage,
                    'status' => 'pending',
                    'due_at' => !empty($validated['follow_up_due_at']) ? $validated['follow_up_due_at'] : now()->addHours(4),
                    'summary' => $validated['follow_up_summary'] ?? 'Follow-up added while updating stage',
                    'metadata' => [
                        'source' => 'stage_update',
                    ],
                    'assigned_to_user_id' => auth()->id(),
                    'created_by_user_id' => auth()->id(),
                ]);
            }
        });

        return back()->with('status', 'Lead stage updated successfully.');
    }

    public function updateLead(Request $request, Lead $lead): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'source_platform' => ['required', 'string', 'in:facebook,instagram,whatsapp,tiktok,google_business,manual'],
            'stage' => ['required', 'string', 'in:new,initial,contacted,visit,negotiation,proposal,booked,confirmed'],
            'status' => ['required', 'string', 'in:open,closed'],
            'follow_up_due_at' => ['nullable', 'date'],
            'follow_up_summary' => ['nullable', 'string', 'max:255'],
        ]);

        $normalizedStage = $this->normalizeLeadStage($validated['stage']);
        $resolvedStatus = $normalizedStage === 'booked' ? 'closed' : $validated['status'];
        $normalizedPhone = $this->normalizePhone((string) ($validated['phone'] ?? ''));

        DB::transaction(function () use ($lead, $validated, $normalizedStage, $resolvedStatus, $normalizedPhone): void {
            $contact = $lead->contact;

            if ($contact === null) {
                $contact = Contact::create([
                    'full_name' => $validated['full_name'],
                    'email' => $validated['email'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'normalized_phone' => $normalizedPhone,
                    'default_source' => $validated['source_platform'],
                    'metadata' => [
                        'created_from' => 'lead_edit',
                    ],
                ]);
            } else {
                $contact->forceFill([
                    'full_name' => $validated['full_name'],
                    'email' => $validated['email'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'normalized_phone' => $normalizedPhone,
                    'default_source' => $validated['source_platform'],
                ])->save();
            }

            $lead->forceFill([
                'contact_id' => $contact->id,
                'source_platform' => $validated['source_platform'],
                'stage' => $normalizedStage,
                'status' => $resolvedStatus,
                'closed_at' => $resolvedStatus === 'closed' ? ($lead->closed_at ?? now()) : null,
                'last_activity_at' => now(),
            ])->save();

            if (!empty($validated['follow_up_due_at']) || !empty($validated['follow_up_summary'])) {
                FollowUp::create([
                    'lead_id' => $lead->id,
                    'contact_id' => $lead->contact_id,
                    'trigger_type' => 'manual_lead_edit',
                    'stage_snapshot' => $normalizedStage,
                    'status' => 'pending',
                    'due_at' => !empty($validated['follow_up_due_at']) ? $validated['follow_up_due_at'] : now()->addHours(4),
                    'summary' => $validated['follow_up_summary'] ?? 'Follow-up added while editing lead',
                    'metadata' => [
                        'source' => 'lead_edit',
                    ],
                    'assigned_to_user_id' => auth()->id(),
                    'created_by_user_id' => auth()->id(),
                ]);
            }
        });

        return back()->with('status', 'Lead updated successfully.');
    }

    public function appointments(): View
    {
        $followUps = FollowUp::query()
            ->with(['lead.contact', 'assignedTo'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('due_at')
            ->paginate(12);

        return view('clinic.appointments', [
            'followUps' => $followUps,
            'pendingCount' => FollowUp::query()->where('status', 'pending')->count(),
            'overdueCount' => FollowUp::query()->where('status', 'pending')->where('due_at', '<', now())->count(),
            'completedTodayCount' => FollowUp::query()->where('status', 'completed')->whereDate('completed_at', now()->toDateString())->count(),
        ]);
    }

    public function updateFollowUpStatus(Request $request, FollowUp $followUp): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:pending,completed,cancelled'],
        ]);

        $followUp->forceFill([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'completed' ? now() : null,
        ])->save();

        return back()->with('status', 'Follow-up status updated.');
    }

    public function consultations(): View
    {
        $activities = LeadActivity::query()
            ->with(['lead.contact'])
            ->orderByDesc('happened_at')
            ->paginate(15);

        return view('clinic.consultations', [
            'activities' => $activities,
        ]);
    }

    public function consultationForm(): View
    {
        return $this->createManualLead();
    }

    public function treatments(): View
    {
        return view('clinic.treatments');
    }

    public function evidence(): View
    {
        return view('clinic.evidence');
    }

    /**
     * @return array<string, string>
     */
    private function stageOptions(): array
    {
        return [
            'new' => 'New',
            'contacted' => 'Contacted',
            'visit' => 'Visit',
            'negotiation' => 'Proposal & Negotiation',
            'booked' => 'Booked',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function sourceOptions(): array
    {
        return [
            'facebook' => 'Facebook',
            'instagram' => 'Instagram',
            'whatsapp' => 'WhatsApp',
            'tiktok' => 'TikTok',
            'google_business' => 'Google Business',
            'manual' => 'Manual',
        ];
    }

    /**
     * @return array<string, array{label: string, stages: array<int, string>}>
     */
    private function leadTabs(): array
    {
        return [
            'all' => [
                'label' => 'All Leads',
                'stages' => [],
            ],
            'new' => [
                'label' => 'New',
                'stages' => ['new', 'initial'],
            ],
            'contacted' => [
                'label' => 'Contacted',
                'stages' => ['contacted'],
            ],
            'visit' => [
                'label' => 'Visit',
                'stages' => ['visit'],
            ],
            'negotiation' => [
                'label' => 'Proposal & Negotiation',
                'stages' => ['negotiation', 'proposal'],
            ],
            'booked' => [
                'label' => 'Booked',
                'stages' => ['booked', 'confirmed'],
            ],
        ];
    }

    private function normalizeLeadStage(string $stage): string
    {
        return match ($stage) {
            'initial' => 'new',
            'proposal' => 'negotiation',
            'confirmed' => 'booked',
            default => $stage,
        };
    }

    private function normalizePhone(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value);

        if (empty($digits)) {
            return null;
        }

        return '+'.$digits;
    }
}
