<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactIdentity;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\WebhookEvent;
use App\Services\Messaging\TwilioWhatsAppService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Throwable;

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
            ->withMax('followUps as last_follow_up_at', 'due_at')
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
            ->orderByDesc('last_follow_up_at')
            ->orderByDesc('last_activity_at');

        $leads = $leadsQuery->get();

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
            'procedureOptions' => $this->procedureInterestOptions(),
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
            'procedureOptions' => $this->procedureInterestOptions(),
        ]);
    }

    public function storeManualLead(Request $request): RedirectResponse
    {
        $procedureKeys = array_keys($this->procedureInterestOptions());

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+92[1-9][0-9]{7,12}$/'],
            'source_platform' => ['required', 'string', 'in:facebook,instagram,whatsapp,tiktok,google_business,manual'],
            'stage' => ['required', 'string', 'in:new'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'follow_up_due_at' => ['required', 'date'],
            'procedure_interests' => ['nullable', 'array'],
            'procedure_interests.*' => ['string', Rule::in($procedureKeys)],
            'procedure_other' => ['nullable', 'string', 'max:255'],
        ], [
            'phone.regex' => 'Use Pakistan number format without starting zero (example: +923001234567).',
        ]);

        $normalizedStage = $this->normalizeLeadStage($validated['stage']);
        $remarks = trim((string) ($validated['remarks'] ?? ''));
        $followUpDueAt = Carbon::parse((string) $validated['follow_up_due_at'], 'Asia/Karachi');
        $normalizedPhone = $this->normalizePhone((string) $validated['phone']);

        if ($normalizedPhone === null) {
            return back()
                ->withErrors(['phone' => 'Enter a valid phone number.'])
                ->withInput();
        }

        if (Contact::query()->where('normalized_phone', $normalizedPhone)->exists()) {
            return back()
                ->withErrors(['phone' => 'This phone number is already registered.'])
                ->withInput();
        }

        $selectedProcedures = collect($validated['procedure_interests'] ?? [])
            ->map(static fn ($value): string => (string) $value)
            ->filter(static fn (string $value): bool => trim($value) !== '')
            ->unique()
            ->values()
            ->all();
        $procedureOther = trim((string) ($validated['procedure_other'] ?? ''));

        DB::transaction(function () use ($validated, $selectedProcedures, $procedureOther, $normalizedStage, $remarks, $followUpDueAt, $normalizedPhone): void {
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

            $lead = Lead::create([
                'contact_id' => $contact->id,
                'source_platform' => $validated['source_platform'],
                'status' => 'open',
                'stage' => $normalizedStage,
                'assigned_to_user_id' => auth()->id(),
                'first_message_at' => now(),
                'last_activity_at' => now(),
                'closed_at' => null,
                'meta' => [
                    'origin' => 'manual_form',
                    'procedures_of_interest' => $selectedProcedures,
                    'procedure_other' => $procedureOther !== '' ? $procedureOther : null,
                ],
            ]);

            if ($remarks !== '') {
                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'contact_id' => $contact->id,
                    'platform' => $validated['source_platform'],
                    'activity_type' => 'manual_note',
                    'direction' => 'inbound',
                    'message_text' => $remarks,
                    'payload' => [
                        'source' => 'manual_form',
                        'procedures_of_interest' => $selectedProcedures,
                        'procedure_other' => $procedureOther !== '' ? $procedureOther : null,
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
                'due_at' => $followUpDueAt,
                'summary' => $remarks !== '' ? $remarks : 'Lead entered from create new lead form',
                'metadata' => [
                    'platform' => $validated['source_platform'],
                    'procedures_of_interest' => $selectedProcedures,
                    'procedure_other' => $procedureOther !== '' ? $procedureOther : null,
                ],
                'assigned_to_user_id' => auth()->id(),
                'created_by_user_id' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('clinicManualLead')
            ->with('status', 'Lead created successfully. First follow-up was added in queue.');
    }

    public function updateLeadStage(
        Request $request,
        Lead $lead,
        TwilioWhatsAppService $twilioWhatsAppService
    ): RedirectResponse
    {
        $validated = $request->validate([
            'stage' => ['required', 'string', 'in:new,initial,contacted,visit,negotiation,proposal,booked,confirmed,not_interested'],
            'follow_up_due_at' => ['nullable', 'date'],
            'follow_up_summary' => ['nullable', 'string', 'max:255'],
        ]);

        $newStage = $this->normalizeLeadStage($validated['stage']);
        $isClosed = $this->isClosedLeadStage($newStage);
        $followUpSummary = trim((string) ($validated['follow_up_summary'] ?? ''));
        $createdFollowUp = null;

        if (
            $newStage === 'booked'
            && !($request->user()?->hasModulePermission('lead_management', 'mark_booked') ?? false)
        ) {
            abort(403, 'You do not have permission to mark leads as booked.');
        }

        DB::transaction(function () use ($lead, $validated, $newStage, $isClosed, &$createdFollowUp): void {
            $lead->forceFill([
                'stage' => $newStage,
                'status' => $isClosed ? 'closed' : 'open',
                'closed_at' => $isClosed ? now() : null,
                'last_activity_at' => now(),
            ])->save();

            if (!empty($validated['follow_up_due_at']) || !empty($validated['follow_up_summary'])) {
                $createdFollowUp = FollowUp::create([
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

        if ($createdFollowUp !== null && $followUpSummary !== '') {
            $sendError = $this->sendFollowUpRemarkToCustomer(
                $createdFollowUp,
                $followUpSummary,
                $twilioWhatsAppService
            );

            if ($sendError !== null) {
                return back()
                    ->withErrors(['whatsapp' => $sendError])
                    ->with('status', 'Lead stage updated and follow-up saved. Customer remark could not be sent.');
            }
        }

        return back()->with('status', 'Lead stage updated successfully.');
    }

    public function updateLead(
        Request $request,
        Lead $lead,
        TwilioWhatsAppService $twilioWhatsAppService
    ): RedirectResponse
    {
        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'source_platform' => ['required', 'string', 'in:facebook,instagram,whatsapp,tiktok,google_business,manual'],
            'stage' => ['required', 'string', 'in:new,initial,contacted,visit,negotiation,proposal,booked,confirmed,not_interested'],
            'status' => ['required', 'string', 'in:open,closed'],
            'follow_up_due_at' => ['nullable', 'date'],
            'follow_up_summary' => ['nullable', 'string', 'max:255'],
        ]);

        $normalizedStage = $this->normalizeLeadStage($validated['stage']);
        $resolvedStatus = $this->isClosedLeadStage($normalizedStage) ? 'closed' : $validated['status'];
        $normalizedPhone = $this->normalizePhone((string) ($validated['phone'] ?? ''));
        $followUpSummary = trim((string) ($validated['follow_up_summary'] ?? ''));
        $createdFollowUp = null;

        if (
            $normalizedStage === 'booked'
            && !($request->user()?->hasModulePermission('lead_management', 'mark_booked') ?? false)
        ) {
            abort(403, 'You do not have permission to mark leads as booked.');
        }

        DB::transaction(function () use ($lead, $validated, $normalizedStage, $resolvedStatus, $normalizedPhone, &$createdFollowUp): void {
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
                $createdFollowUp = FollowUp::create([
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

        if ($createdFollowUp !== null && $followUpSummary !== '') {
            $sendError = $this->sendFollowUpRemarkToCustomer(
                $createdFollowUp,
                $followUpSummary,
                $twilioWhatsAppService
            );

            if ($sendError !== null) {
                return back()
                    ->withErrors(['whatsapp' => $sendError])
                    ->with('status', 'Lead updated and follow-up saved. Customer remark could not be sent.');
            }
        }

        return back()->with('status', 'Lead updated successfully.');
    }

    public function sendWhatsAppMessage(
        Request $request,
        Lead $lead,
        TwilioWhatsAppService $twilioWhatsAppService
    ): RedirectResponse {
        $validated = $request->validate([
            'message' => ['required', 'string', 'max:4096'],
        ]);

        $contact = $lead->contact;

        if ($contact === null) {
            return back()->withErrors([
                'whatsapp' => 'Lead contact is missing. Please update this lead before sending a WhatsApp message.',
            ]);
        }

        $identity = ContactIdentity::query()
            ->where('contact_id', $contact->id)
            ->where('platform', 'whatsapp')
            ->latest('updated_at')
            ->first();

        $recipient = (string) ($identity?->external_id ?? $contact->phone ?? '');

        if (trim($recipient) === '') {
            return back()->withErrors([
                'whatsapp' => 'No WhatsApp number found for this lead.',
            ]);
        }

        $message = trim((string) $validated['message']);

        if ($message === '') {
            return back()->withErrors([
                'whatsapp' => 'Message body cannot be empty.',
            ]);
        }

        try {
            $sentAt = now();
            $result = $twilioWhatsAppService->sendTextMessage($recipient, $message);

            DB::transaction(function () use ($lead, $contact, $message, $result, $sentAt): void {
                $platformMessageId = (string) ($result['platform_message_id'] ?? '');
                $recipientRaw = (string) ($result['to'] ?? '');
                $recipientId = preg_replace('/\D+/', '', $recipientRaw) ?? '';
                $fallbackRecipientId = preg_replace('/\D+/', '', (string) ($contact->phone ?? '')) ?? '';
                $recipientId = $recipientId !== '' ? $recipientId : $fallbackRecipientId;

                if ($recipientId !== '') {
                    ContactIdentity::updateOrCreate(
                        [
                            'platform' => 'whatsapp',
                            'external_id' => $recipientId,
                        ],
                        [
                            'contact_id' => $contact->id,
                            'display_name' => $contact->full_name,
                            'raw_payload' => [
                                'source' => 'outbound_message',
                            ],
                            'last_seen_at' => $sentAt,
                        ]
                    );
                }

                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'contact_id' => $contact->id,
                    'platform' => 'whatsapp',
                    'activity_type' => 'message_sent',
                    'direction' => 'outbound',
                    'platform_message_id' => $platformMessageId,
                    'message_text' => $message,
                    'payload' => $result['response'] ?? [],
                    'happened_at' => $sentAt,
                    'created_by_user_id' => auth()->id(),
                ]);

                $lead->forceFill([
                    'last_activity_at' => $sentAt,
                    'first_message_at' => $lead->first_message_at ?? $sentAt,
                ])->save();
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()->withErrors([
                'whatsapp' => $exception->getMessage(),
            ]);
        }

        return back()->with('status', 'WhatsApp message sent successfully.');
    }

    public function whatsappDemo(): View
    {
        $today = now()->toDateString();
        $webhookUrl = trim((string) config('crm.whatsapp.twilio.webhook_url', ''));

        if ($webhookUrl === '') {
            $webhookUrl = url('/api/webhooks/whatsapp');
        }

        $appUrl = (string) config('app.url', '');
        $isLocalWebhookUrl = str_contains($webhookUrl, 'localhost') || str_contains($webhookUrl, '127.0.0.1');

        $inboundWebhookEvents = WebhookEvent::query()
            ->where('platform', 'whatsapp')
            ->latest('received_at')
            ->limit(25)
            ->get()
            ->map(fn (WebhookEvent $event): array => $this->formatWebhookEventForDemo($event));

        $whatsAppActivities = LeadActivity::query()
            ->with(['lead.contact'])
            ->where('platform', 'whatsapp')
            ->latest('happened_at')
            ->limit(25)
            ->get();

        return view('clinic.whatsappDemo', [
            'stats' => [
                'inbound_today' => LeadActivity::query()
                    ->where('platform', 'whatsapp')
                    ->where('direction', 'inbound')
                    ->whereDate('happened_at', $today)
                    ->count(),
                'outbound_today' => LeadActivity::query()
                    ->where('platform', 'whatsapp')
                    ->where('direction', 'outbound')
                    ->whereDate('happened_at', $today)
                    ->count(),
                'webhooks_today' => WebhookEvent::query()
                    ->where('platform', 'whatsapp')
                    ->whereDate('received_at', $today)
                    ->count(),
                'manual_alerts' => WebhookEvent::query()
                    ->where('platform', 'whatsapp')
                    ->where(function (Builder $query): void {
                        $query
                            ->where('status', 'failed')
                            ->orWhere('error_message', 'like', '%manual%');
                    })
                    ->count(),
            ],
            'setup' => [
                'sid_set' => !empty((string) config('services.twilio.account_sid', '')),
                'token_set' => !empty((string) config('services.twilio.auth_token', '')),
                'from_set' => !empty((string) config('crm.whatsapp.twilio.from', '')),
                'signature_validation' => (bool) config('crm.whatsapp.twilio.validate_signature', true),
                'app_url' => $appUrl,
                'webhook_url' => $webhookUrl,
                'webhook_public_https' => !$isLocalWebhookUrl && str_starts_with(strtolower($webhookUrl), 'https://'),
            ],
            'webhookUrl' => $webhookUrl,
            'inboundWebhookEvents' => $inboundWebhookEvents,
            'whatsAppActivities' => $whatsAppActivities,
        ]);
    }

    public function whatsappDemoSend(
        Request $request,
        TwilioWhatsAppService $twilioWhatsAppService
    ): RedirectResponse {
        $validated = $request->validate([
            'to' => ['required', 'string', 'max:30'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'message' => ['required', 'string', 'max:1600'],
        ]);

        $normalizedPhone = $this->normalizePhone((string) $validated['to']);

        if ($normalizedPhone === null) {
            return back()
                ->withErrors(['whatsapp_demo' => 'Please enter a valid WhatsApp phone number.'])
                ->withInput();
        }

        $message = trim((string) $validated['message']);

        if ($message === '') {
            return back()
                ->withErrors(['whatsapp_demo' => 'Message cannot be empty.'])
                ->withInput();
        }

        try {
            $sentAt = now();
            $result = $twilioWhatsAppService->sendTextMessage($normalizedPhone, $message);

            DB::transaction(function () use ($validated, $normalizedPhone, $message, $result, $sentAt): void {
                $contactName = trim((string) ($validated['contact_name'] ?? ''));
                $recipientId = preg_replace('/\D+/', '', $normalizedPhone) ?? '';

                $contact = Contact::query()
                    ->where('normalized_phone', $normalizedPhone)
                    ->first();

                if ($contact === null) {
                    $contact = Contact::create([
                        'full_name' => $contactName !== '' ? $contactName : null,
                        'phone' => $normalizedPhone,
                        'normalized_phone' => $normalizedPhone,
                        'default_source' => 'whatsapp',
                        'metadata' => [
                            'created_from' => 'whatsapp_demo_send',
                        ],
                    ]);
                } elseif ($contactName !== '' && empty($contact->full_name)) {
                    $contact->forceFill([
                        'full_name' => $contactName,
                    ])->save();
                }

                if ($recipientId !== '') {
                    ContactIdentity::updateOrCreate(
                        [
                            'platform' => 'whatsapp',
                            'external_id' => $recipientId,
                        ],
                        [
                            'contact_id' => $contact->id,
                            'display_name' => $contact->full_name,
                            'raw_payload' => [
                                'source' => 'whatsapp_demo_send',
                            ],
                            'last_seen_at' => $sentAt,
                        ]
                    );
                }

                $lead = Lead::query()
                    ->where('contact_id', $contact->id)
                    ->where('status', 'open')
                    ->latest('updated_at')
                    ->first();

                if ($lead === null) {
                    $lead = Lead::create([
                        'contact_id' => $contact->id,
                        'source_platform' => 'whatsapp',
                        'status' => 'open',
                        'stage' => 'initial',
                        'assigned_to_user_id' => auth()->id(),
                        'first_message_at' => $sentAt,
                        'last_activity_at' => $sentAt,
                        'meta' => [
                            'origin' => 'whatsapp_demo_send',
                        ],
                    ]);
                }

                LeadActivity::create([
                    'lead_id' => $lead->id,
                    'contact_id' => $contact->id,
                    'platform' => 'whatsapp',
                    'activity_type' => 'message_sent',
                    'direction' => 'outbound',
                    'platform_message_id' => (string) ($result['platform_message_id'] ?? ''),
                    'message_text' => $message,
                    'payload' => $result['response'] ?? [],
                    'happened_at' => $sentAt,
                    'created_by_user_id' => auth()->id(),
                ]);

                $lead->forceFill([
                    'last_activity_at' => $sentAt,
                    'first_message_at' => $lead->first_message_at ?? $sentAt,
                ])->save();
            });
        } catch (Throwable $exception) {
            report($exception);

            return back()
                ->withErrors(['whatsapp_demo' => $exception->getMessage()])
                ->withInput();
        }

        return redirect()
            ->route('clinicWhatsAppDemo')
            ->with('status', 'WhatsApp message sent from demo page.');
    }

    public function appointments(Request $request): View
    {
        $validated = $request->validate([
            'tab' => ['nullable', 'string', 'in:today,pending,upcoming'],
        ]);

        $activeTab = (string) ($validated['tab'] ?? 'today');
        $now = now('Asia/Karachi');
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();

        $tabScopedPendingQuery = FollowUp::query()
            ->where('status', 'pending')
            ->when($activeTab === 'today', fn (Builder $query): Builder => $query->whereBetween('due_at', [$todayStart, $todayEnd]))
            ->when($activeTab === 'pending', fn (Builder $query): Builder => $query->where('due_at', '<', $now))
            ->when($activeTab === 'upcoming', fn (Builder $query): Builder => $query->where('due_at', '>', $todayEnd));

        $leadIdsSubQuery = (clone $tabScopedPendingQuery)
            ->select('lead_id')
            ->distinct();

        $leads = Lead::query()
            ->with(['contact'])
            ->withCount('followUps')
            ->withMax('followUps as last_follow_up_at', 'due_at')
            ->whereIn('id', $leadIdsSubQuery)
            ->orderByDesc('last_follow_up_at')
            ->orderByDesc('last_activity_at')
            ->get();

        return view('clinic.appointments', [
            'leads' => $leads,
            'activeTab' => $activeTab,
            'todayCount' => FollowUp::query()
                ->where('status', 'pending')
                ->whereBetween('due_at', [$todayStart, $todayEnd])
                ->count(),
            'pendingCount' => FollowUp::query()
                ->where('status', 'pending')
                ->where('due_at', '<', $now)
                ->count(),
            'upcomingCount' => FollowUp::query()
                ->where('status', 'pending')
                ->where('due_at', '>', $todayEnd)
                ->count(),
            'stages' => $this->stageOptions(),
            'procedureOptions' => $this->procedureInterestOptions(),
        ]);
    }

    public function leadFollowUp(Lead $lead): View
    {
        $lead->load(['contact']);

        $followUps = FollowUp::query()
            ->with(['createdBy'])
            ->where('lead_id', $lead->id)
            ->orderByDesc('created_at')
            ->get();

        return view('clinic.leadFollowUp', [
            'lead' => $lead,
            'followUps' => $followUps,
            'followUpMethods' => $this->followUpMethodOptions(),
            'followUpStages' => $this->followUpFormStageOptions(),
            'sourceOptions' => $this->sourceOptions(),
            'procedureOptions' => $this->procedureInterestOptions(),
        ]);
    }

    public function storeLeadFollowUp(Request $request, Lead $lead): RedirectResponse
    {
        $methodOptions = array_keys($this->followUpMethodOptions());
        $stageOptions = array_keys($this->followUpFormStageOptions());

        $validated = $request->validate([
            'follow_up_method' => ['required', 'string', Rule::in($methodOptions)],
            'stage' => ['required', 'string', Rule::in($stageOptions)],
        ]);

        $nextStage = $this->normalizeLeadStage((string) $validated['stage']);
        $isClosedStage = $this->isClosedLeadStage($nextStage);

        if (
            $nextStage === 'booked'
            && !($request->user()?->hasModulePermission('lead_management', 'mark_booked') ?? false)
        ) {
            abort(403, 'You do not have permission to mark leads as booked.');
        }

        $validated = array_merge($validated, $request->validate([
            'next_follow_up_due_at' => [
                Rule::requiredIf(!$isClosedStage),
                'nullable',
                'date',
            ],
            'remarks' => [
                Rule::requiredIf(!$isClosedStage),
                'nullable',
                'string',
                'max:1000',
            ],
        ], [
            'next_follow_up_due_at.required' => 'Next follow-up date and time is required.',
            'remarks.required' => 'Remarks are required.',
        ]));

        $resolvedAt = now('Asia/Karachi');
        $remarks = trim((string) ($validated['remarks'] ?? ''));

        $nextDueAt = !$isClosedStage && !empty($validated['next_follow_up_due_at'])
            ? Carbon::parse((string) $validated['next_follow_up_due_at'], 'Asia/Karachi')
            : null;

        DB::transaction(function () use ($lead, $validated, $nextStage, $isClosedStage, $resolvedAt, $nextDueAt, $remarks): void {
            FollowUp::query()
                ->where('lead_id', $lead->id)
                ->where('status', 'pending')
                ->update([
                    'status' => 'completed',
                    'completed_at' => $resolvedAt,
                    'stage_snapshot' => $nextStage,
                    'updated_at' => $resolvedAt,
                ]);

            FollowUp::create([
                'lead_id' => $lead->id,
                'contact_id' => $lead->contact_id,
                'trigger_type' => (string) $validated['follow_up_method'],
                'stage_snapshot' => $nextStage,
                'status' => $isClosedStage ? 'completed' : 'pending',
                'due_at' => $isClosedStage ? $resolvedAt : $nextDueAt,
                'completed_at' => $isClosedStage ? $resolvedAt : null,
                'summary' => $remarks !== '' ? $remarks : ($isClosedStage
                    ? 'Follow-up closed from follow-up page'
                    : 'Next follow-up scheduled from follow-up page'),
                'metadata' => [
                    'source' => 'lead_follow_up_page',
                    'method' => (string) $validated['follow_up_method'],
                ],
                'assigned_to_user_id' => $lead->assigned_to_user_id ?: auth()->id(),
                'created_by_user_id' => auth()->id(),
            ]);

            $lead->forceFill([
                'stage' => $nextStage,
                'status' => $isClosedStage ? 'closed' : 'open',
                'closed_at' => $isClosedStage ? ($lead->closed_at ?? $resolvedAt) : null,
                'last_activity_at' => $resolvedAt,
            ])->save();
        });

        return redirect()
            ->route('clinicLeadFollowUp', $lead)
            ->with('status', 'Follow-up saved successfully.');
    }

    public function updateFollowUpStatus(
        Request $request,
        FollowUp $followUp,
        TwilioWhatsAppService $twilioWhatsAppService
    ): RedirectResponse
    {
        $validated = $request->validate([
            'stage' => ['required', 'string', 'in:new,initial,contacted,visit,negotiation,proposal,booked,confirmed,not_interested'],
            'next_follow_up_due_at' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'send_remarks_to_customer' => ['nullable', 'boolean'],
        ]);

        $nextStage = $this->normalizeLeadStage($validated['stage']);
        $isClosedStage = $this->isClosedLeadStage($nextStage);
        $nextDueAt = !empty($validated['next_follow_up_due_at'])
            ? Carbon::parse((string) $validated['next_follow_up_due_at'], 'Asia/Karachi')
            : null;
        $remarks = trim((string) ($validated['remarks'] ?? ''));
        $shouldSendRemarks = (bool) ($validated['send_remarks_to_customer'] ?? false);
        $nextFollowUp = null;

        if (
            $nextStage === 'booked'
            && !($request->user()?->hasModulePermission('lead_management', 'mark_booked') ?? false)
        ) {
            abort(403, 'You do not have permission to mark leads as booked.');
        }

        if (!$isClosedStage && empty($nextDueAt)) {
            return back()
                ->withErrors(['next_follow_up_due_at' => 'Next follow-up date and time is required for this stage.'])
                ->withInput();
        }

        DB::transaction(function () use ($followUp, $nextStage, $isClosedStage, $nextDueAt, $remarks, &$nextFollowUp): void {
            $resolvedAt = now();

            $followUp->forceFill([
                'status' => 'completed',
                'completed_at' => $resolvedAt,
                'stage_snapshot' => $nextStage,
                'summary' => $remarks !== '' ? $remarks : $followUp->summary,
            ])->save();

            $lead = $followUp->lead;

            if ($lead !== null) {
                $lead->forceFill([
                    'stage' => $nextStage,
                    'status' => $isClosedStage ? 'closed' : 'open',
                    'closed_at' => $isClosedStage ? ($lead->closed_at ?? $resolvedAt) : null,
                    'last_activity_at' => $resolvedAt,
                ])->save();
            }

            if (!$isClosedStage && !empty($nextDueAt)) {
                $nextFollowUp = FollowUp::create([
                    'lead_id' => $followUp->lead_id,
                    'contact_id' => $followUp->contact_id,
                    'trigger_type' => 'manual_follow_up_update',
                    'stage_snapshot' => $nextStage,
                    'status' => 'pending',
                    'due_at' => $nextDueAt,
                    'summary' => $remarks !== '' ? $remarks : 'Next follow-up scheduled from follow-up queue',
                    'metadata' => [
                        'source' => 'follow_up_queue',
                        'previous_follow_up_id' => $followUp->id,
                    ],
                    'assigned_to_user_id' => $followUp->assigned_to_user_id ?: auth()->id(),
                    'created_by_user_id' => auth()->id(),
                ]);
            }
        });

        if ($shouldSendRemarks && $remarks !== '') {
            $targetFollowUp = $nextFollowUp instanceof FollowUp
                ? $nextFollowUp
                : ($followUp->fresh() ?? $followUp);
            $sendError = $this->sendFollowUpRemarkToCustomer(
                $targetFollowUp,
                $remarks,
                $twilioWhatsAppService
            );

            if ($sendError !== null) {
                return back()
                    ->withErrors(['whatsapp' => $sendError])
                    ->with('status', 'Follow-up saved, but remarks were not sent to customer.');
            }
        }

        if ($isClosedStage) {
            return back()->with('status', 'Follow-up saved and lead closed.');
        }

        return back()->with('status', 'Follow-up saved and next follow-up scheduled.');
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
            'not_interested' => 'Not Interested',
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
            'manual' => 'Walk In Lead',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function followUpMethodOptions(): array
    {
        return [
            'call' => 'Call',
            'whatsapp' => 'WhatsApp',
            'sms' => 'SMS',
            'walkin' => 'Walk In',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function followUpFormStageOptions(): array
    {
        return [
            'new' => 'New',
            'contacted' => 'Contacted',
            'negotiation' => 'Negotiation & Proposal',
            'booked' => 'Booked',
            'not_interested' => 'Not Interested',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function procedureInterestOptions(): array
    {
        return [
            'laser_hair_removal' => 'Laser Hair Removal',
            'acne_acne_scars' => 'Acne / Acne Scars',
            'pigmentation_melasma_freckles' => 'Pigmentation / Melasma / Freckles',
            'anti_aging_face_lifting' => 'Anti-Aging / Face Lifting',
            'botox_dermal_fillers' => 'Botox / Dermal Fillers',
            'prp_face_hair' => 'PRP (Face / Hair)',
            'hair_restoration_hair_fall_treatment' => 'Hair Restoration / Hair Fall Treatment',
            'skin_tightening_hifu_rf' => 'Skin Tightening (HIFU / RF)',
            'chemical_peels_carbon_peel' => 'Chemical Peels / Carbon Peel',
            'body_contouring_fat_reduction' => 'Body Contouring / Fat Reduction',
            'stretch_marks' => 'Stretch Marks',
            'keloid_hypertrophic_scars' => 'Keloid / Hypertrophic Scars',
            'skin_whitening_brightening' => 'Skin Whitening / Brightening',
            'cosmetic_surgical_consultation' => 'Cosmetic / Surgical Consultation',
            'other' => 'Other',
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
            'negotiation' => [
                'label' => 'Proposal & Negotiation',
                'stages' => ['negotiation', 'proposal'],
            ],
            'booked' => [
                'label' => 'Booked',
                'stages' => ['booked', 'confirmed'],
            ],
            'not_interested' => [
                'label' => 'Not Interested',
                'stages' => ['not_interested'],
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

    private function isClosedLeadStage(string $stage): bool
    {
        return in_array($stage, ['booked', 'not_interested'], true);
    }

    private function normalizePhone(string $value): ?string
    {
        $digits = preg_replace('/\D+/', '', $value);

        if (empty($digits)) {
            return null;
        }

        if (str_starts_with($digits, '0092')) {
            $digits = substr($digits, 2);
        }

        if (str_starts_with($digits, '92')) {
            $local = ltrim((string) substr($digits, 2), '0');

            return $local !== '' ? '+92'.$local : null;
        }

        if (str_starts_with($digits, '0')) {
            $local = ltrim($digits, '0');

            return $local !== '' ? '+92'.$local : null;
        }

        return '+'.$digits;
    }

    private function sendFollowUpRemarkToCustomer(
        FollowUp $followUp,
        string $remarks,
        TwilioWhatsAppService $twilioWhatsAppService
    ): ?string {
        $remarks = trim($remarks);

        if ($remarks === '') {
            return null;
        }

        $contact = $followUp->contact;
        $identity = null;
        $recipient = '';

        if ($contact !== null) {
            $recipient = trim((string) ($contact->phone ?? ''));

            if ($recipient === '') {
                $identity = ContactIdentity::query()
                    ->where('contact_id', $contact->id)
                    ->where('platform', 'whatsapp')
                    ->latest('updated_at')
                    ->first();

                $recipient = trim((string) ($identity?->external_id ?? ''));
            }
        }

        if ($contact === null || $recipient === '') {
            return 'Follow-up saved, but no customer phone was found to send remarks via Twilio.';
        }

        try {
            $sentAt = now();
            $result = $twilioWhatsAppService->sendTextMessage($recipient, $remarks);

            LeadActivity::create([
                'lead_id' => $followUp->lead_id,
                'contact_id' => $followUp->contact_id,
                'platform' => 'whatsapp',
                'activity_type' => 'follow_up_remark_sent',
                'direction' => 'outbound',
                'platform_message_id' => (string) ($result['platform_message_id'] ?? ''),
                'message_text' => $remarks,
                'payload' => $result['response'] ?? [],
                'happened_at' => $sentAt,
                'created_by_user_id' => auth()->id(),
            ]);

            $metadata = $followUp->metadata ?? [];
            $metadata['customer_remark'] = $remarks;
            $metadata['customer_remark_message_id'] = $result['platform_message_id'] ?? null;
            $metadata['customer_remark_sent_at'] = $sentAt->toISOString();

            $followUp->forceFill([
                'metadata' => $metadata,
            ])->save();

            $lead = $followUp->lead;

            if ($lead !== null) {
                $lead->forceFill([
                    'last_activity_at' => $sentAt,
                    'first_message_at' => $lead->first_message_at ?? $sentAt,
                ])->save();
            }
        } catch (Throwable $exception) {
            report($exception);

            return $exception->getMessage();
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function formatWebhookEventForDemo(WebhookEvent $event): array
    {
        $payload = is_array($event->payload) ? $event->payload : [];
        $crmPayload = is_array($payload['_crm'] ?? null) ? $payload['_crm'] : [];
        $isTwilio = isset($payload['MessageSid']) || isset($payload['SmsMessageSid']) || isset($payload['WaId']);

        $from = $isTwilio
            ? (string) ($payload['From'] ?? $payload['WaId'] ?? 'Unknown')
            : (string) data_get(
                $payload,
                'entry.0.changes.0.value.contacts.0.wa_id',
                data_get($payload, 'entry.0.changes.0.value.messages.0.from', 'Unknown')
            );

        $body = $isTwilio
            ? trim((string) ($payload['Body'] ?? ''))
            : trim((string) (
                data_get($payload, 'entry.0.changes.0.value.messages.0.text.body')
                ?? data_get($payload, 'entry.0.changes.0.value.messages.0.button.text')
                ?? ''
            ));

        if ($body === '') {
            $body = '[non-text message]';
        }

        $isHighlighted = $event->status === 'failed'
            || (bool) ($crmPayload['manual_follow_up_required'] ?? false)
            || stripos((string) ($event->error_message ?? ''), 'manual') !== false;

        return [
            'id' => $event->id,
            'event_id' => $event->event_id,
            'status' => $event->status,
            'received_at' => $event->received_at,
            'from' => $from,
            'body' => $body,
            'provider' => $isTwilio ? 'twilio' : 'meta',
            'lead_id' => $crmPayload['lead_id'] ?? null,
            'error_message' => $event->error_message,
            'is_highlighted' => $isHighlighted,
        ];
    }
}
