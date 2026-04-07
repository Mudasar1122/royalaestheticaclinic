<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactIdentity;
use App\Models\FollowUp;
use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Services\Messaging\TwilioWhatsAppService;
use App\Support\LeadExportBuilder;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class ClinicController extends Controller
{
    public function leads(Request $request): View
    {
        $leadTabs = $this->leadTabs();
        $filters = $this->validatedLeadFilters($request);
        $user = $request->user();

        $leads = $this->decorateLeadListingQuery(
            $this->applyLeadTabScope(
                $this->buildLeadBaseQuery($user, $filters),
                $filters['tab'],
                $leadTabs
            )
        )->get();

        $tabCounts = [];
        foreach (array_keys($leadTabs) as $tabKey) {
            $tabCounts[$tabKey] = $this->applyLeadTabScope(
                $this->buildLeadBaseQuery($user, $filters),
                $tabKey,
                $leadTabs
            )->count();
        }

        return view('clinic.leads', [
            'leads' => $leads,
            'stages' => $this->stageOptions(),
            'sources' => $this->sourceOptions(),
            'genderOptions' => $this->genderOptions(),
            'procedureOptions' => $this->procedureInterestOptions(),
            'leadTabs' => $leadTabs,
            'activeTab' => $filters['tab'],
            'tabCounts' => $tabCounts,
            'filters' => $filters,
        ]);
    }

    public function exportLeads(Request $request)
    {
        $leadTabs = $this->leadTabs();
        $filters = $this->validatedLeadFilters($request);
        $validated = $request->validate([
            'scope' => ['required', 'string', Rule::in(['all', 'selected'])],
            'format' => ['required', 'string', Rule::in(['excel', 'pdf'])],
            'lead_ids' => ['nullable', 'string'],
        ]);

        $scope = (string) $validated['scope'];
        $format = (string) $validated['format'];
        $selectedLeadIds = $this->parseSelectedLeadIds((string) ($validated['lead_ids'] ?? ''));

        if ($scope === 'selected' && empty($selectedLeadIds)) {
            return back()->withErrors(['export' => 'Select at least one lead to export.']);
        }

        $leadsQuery = $this->decorateLeadListingQuery(
            $this->applyLeadTabScope(
                $this->buildLeadBaseQuery($request->user(), $filters),
                $filters['tab'],
                $leadTabs
            )
        );

        if ($scope === 'selected') {
            $leadsQuery->whereIn('leads.id', $selectedLeadIds);
        }

        $leads = $leadsQuery->get();

        if ($leads->isEmpty()) {
            return back()->withErrors(['export' => 'No leads matched the current export selection.']);
        }

        $exportBuilder = new LeadExportBuilder(
            $this->stageOptions(),
            $this->sourceOptions(),
            $this->procedureInterestOptions()
        );

        $generatedAt = now('Asia/Karachi')->format('d M Y h:i A').' PKT';
        $scopeLabel = $scope === 'selected' ? 'Selected Leads' : 'All Filtered Leads';
        $context = [
            'title' => 'CRM Leads Export',
            'scope_label' => $scopeLabel,
            'generated_at' => $generatedAt,
            'filter_summary' => $this->leadFilterSummary($filters, $leadTabs),
        ];
        $timestamp = now('Asia/Karachi')->format('Ymd_His');

        if ($format === 'excel') {
            return response($exportBuilder->toExcel($leads, $context), 200, [
                'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
                'Content-Disposition' => 'attachment; filename="clinic_leads_'.$scope.'_'.$timestamp.'.xls"',
                'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
                'Pragma' => 'public',
            ]);
        }

        return response($exportBuilder->toPdf($leads, $context), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="clinic_leads_'.$scope.'_'.$timestamp.'.pdf"',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

    public function deletedLeads(): View
    {
        $leads = Lead::onlyTrashed()
            ->with(['contact'])
            ->withMax('followUps as last_follow_up_at', 'due_at')
            ->orderByDesc('deleted_at')
            ->orderByDesc('last_activity_at')
            ->get();

        return view('clinic.deletedLeads', [
            'leads' => $leads,
            'sources' => $this->sourceOptions(),
        ]);
    }

    public function createManualLead(): View
    {
        return view('clinic.manualLead', [
            'sources' => $this->sourceOptions(),
            'stages' => $this->stageOptions(),
            'genderOptions' => $this->genderOptions(),
            'procedureOptions' => $this->procedureInterestOptions(),
        ]);
    }

    public function storeManualLead(Request $request): RedirectResponse
    {
        $genderKeys = array_keys($this->genderOptions());
        $sourceKeys = array_keys($this->sourceOptions());
        $procedureKeys = array_keys($this->procedureInterestOptions());

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'gender' => ['required', 'string', Rule::in($genderKeys)],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['required', 'string', 'max:30', 'regex:/^\+92[1-9][0-9]{7,12}$/'],
            'source_platform' => ['required', 'string', Rule::in($sourceKeys)],
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
        $followUpDueAt = $this->parsePakistanDateTimeToUtc((string) $validated['follow_up_due_at']);
        $normalizedPhone = $this->normalizePhone((string) $validated['phone']);

        if ($normalizedPhone === null) {
            return back()
                ->withErrors(['phone' => 'Enter a valid phone number.'])
                ->withInput();
        }

        $existingContact = Contact::query()
            ->where('normalized_phone', $normalizedPhone)
            ->first();

        if ($existingContact !== null) {
            $existingLeadName = trim((string) ($existingContact->full_name ?? ''));
            $duplicateMessage = $existingLeadName !== ''
                ? 'This phone number is already registered for '.$existingLeadName.'.'
                : 'This phone number is already registered.';

            return back()
                ->withErrors(['phone' => $duplicateMessage])
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
                'gender' => $validated['gender'],
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

        $redirectRoute = $request->user()?->hasModulePermission('lead_management', 'manage_followups')
            ? 'clinicAppointments'
            : 'clinicManualLead';

        return redirect()
            ->route($redirectRoute)
            ->with('status', 'Lead created successfully. First follow-up was added in queue.');
    }

    public function updateLeadStage(
        Request $request,
        Lead $lead,
        TwilioWhatsAppService $twilioWhatsAppService
    ): RedirectResponse
    {
        $this->authorizeLeadAccess($request->user(), $lead);

        $validated = $request->validate([
            'stage' => ['required', 'string', Rule::in($this->leadStageValidationOptions())],
            'follow_up_due_at' => ['nullable', 'date'],
            'follow_up_summary' => ['nullable', 'string', 'max:255'],
        ]);

        $newStage = $this->normalizeLeadStage($validated['stage']);
        $isClosed = $this->isClosedLeadStage($newStage);
        $followUpSummary = trim((string) ($validated['follow_up_summary'] ?? ''));
        $createdFollowUp = null;
        $followUpDueAt = $this->parsePakistanDateTimeToUtc($validated['follow_up_due_at'] ?? null);

        if (
            $newStage === 'booked'
            && !($request->user()?->hasModulePermission('lead_management', 'mark_booked') ?? false)
        ) {
            abort(403, 'You do not have permission to mark leads as booked.');
        }

        $this->ensureProcedureAttemptedTransitionAllowed($lead, $newStage);

        DB::transaction(function () use ($lead, $validated, $newStage, $isClosed, $followUpDueAt, &$createdFollowUp): void {
            $lead->forceFill([
                'stage' => $newStage,
                'status' => $isClosed ? 'closed' : 'open',
                'closed_at' => $isClosed ? ($lead->closed_at ?? now()) : null,
                'last_activity_at' => now(),
            ])->save();

            if (!empty($validated['follow_up_due_at']) || !empty($validated['follow_up_summary'])) {
                $createdFollowUp = FollowUp::create([
                    'lead_id' => $lead->id,
                    'contact_id' => $lead->contact_id,
                    'trigger_type' => 'manual_stage_update',
                    'stage_snapshot' => $lead->stage,
                    'status' => 'pending',
                    'due_at' => $followUpDueAt ?? now()->addHours(4),
                    'summary' => $validated['follow_up_summary'] ?? 'Follow-up added while updating stage',
                    'metadata' => [
                        'source' => 'stage_update',
                    ],
                    'assigned_to_user_id' => $lead->assigned_to_user_id ?: auth()->id(),
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
        $this->authorizeLeadAccess($request->user(), $lead);

        $genderKeys = array_keys($this->genderOptions());
        $sourceKeys = array_keys($this->sourceOptions());
        $procedureKeys = array_keys($this->procedureInterestOptions());

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'max:120'],
            'gender' => ['required', 'string', Rule::in($genderKeys)],
            'email' => ['nullable', 'email', 'max:120'],
            'phone' => ['nullable', 'string', 'max:30'],
            'source_platform' => ['required', 'string', Rule::in($sourceKeys)],
            'stage' => ['required', 'string', Rule::in($this->leadStageValidationOptions())],
            'status' => ['required', 'string', 'in:open,closed'],
            'procedure_interests_submitted' => ['nullable', 'boolean'],
            'procedure_interests' => ['nullable', 'array'],
            'procedure_interests.*' => ['string', Rule::in($procedureKeys)],
            'procedure_other' => ['nullable', 'string', 'max:255'],
            'follow_up_due_at' => ['nullable', 'date'],
            'follow_up_summary' => ['nullable', 'string', 'max:255'],
        ]);

        $normalizedStage = $this->normalizeLeadStage($validated['stage']);
        $resolvedStatus = $this->isClosedLeadStage($normalizedStage) ? 'closed' : $validated['status'];
        $normalizedPhone = $this->normalizePhone((string) ($validated['phone'] ?? ''));
        $proceduresWereSubmitted = filter_var(
            $validated['procedure_interests_submitted'] ?? false,
            FILTER_VALIDATE_BOOLEAN
        );
        $selectedProcedureInterests = collect($validated['procedure_interests'] ?? [])
            ->map(static fn ($value): string => (string) $value)
            ->filter(static fn (string $value): bool => $value !== '')
            ->unique()
            ->values()
            ->all();
        $procedureOther = trim((string) ($validated['procedure_other'] ?? ''));

        if (!in_array('other', $selectedProcedureInterests, true)) {
            $procedureOther = '';
        }

        $followUpSummary = trim((string) ($validated['follow_up_summary'] ?? ''));
        $createdFollowUp = null;
        $followUpDueAt = $this->parsePakistanDateTimeToUtc($validated['follow_up_due_at'] ?? null);

        if (
            $normalizedStage === 'booked'
            && !($request->user()?->hasModulePermission('lead_management', 'mark_booked') ?? false)
        ) {
            abort(403, 'You do not have permission to mark leads as booked.');
        }

        $this->ensureProcedureAttemptedTransitionAllowed($lead, $normalizedStage);

        DB::transaction(function () use (
            $lead,
            $validated,
            $normalizedStage,
            $resolvedStatus,
            $normalizedPhone,
            $proceduresWereSubmitted,
            $selectedProcedureInterests,
            $procedureOther,
            $followUpDueAt,
            &$createdFollowUp
        ): void {
            $contact = $lead->contact;

            if ($contact === null) {
                $contact = Contact::create([
                    'full_name' => $validated['full_name'],
                    'gender' => $validated['gender'],
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
                    'gender' => $validated['gender'],
                    'email' => $validated['email'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'normalized_phone' => $normalizedPhone,
                    'default_source' => $validated['source_platform'],
                ])->save();
            }

            $leadMeta = is_array($lead->meta) ? $lead->meta : [];

            if ($proceduresWereSubmitted) {
                $leadMeta['procedures_of_interest'] = $selectedProcedureInterests;
                $leadMeta['procedure_other'] = $procedureOther !== '' ? $procedureOther : null;
            }

            $lead->forceFill([
                'contact_id' => $contact->id,
                'source_platform' => $validated['source_platform'],
                'stage' => $normalizedStage,
                'status' => $resolvedStatus,
                'closed_at' => $resolvedStatus === 'closed' ? ($lead->closed_at ?? now()) : null,
                'last_activity_at' => now(),
                'meta' => $leadMeta,
            ])->save();

            if (!empty($validated['follow_up_due_at']) || !empty($validated['follow_up_summary'])) {
                $createdFollowUp = FollowUp::create([
                    'lead_id' => $lead->id,
                    'contact_id' => $lead->contact_id,
                    'trigger_type' => 'manual_lead_edit',
                    'stage_snapshot' => $normalizedStage,
                    'status' => 'pending',
                    'due_at' => $followUpDueAt ?? now()->addHours(4),
                    'summary' => $validated['follow_up_summary'] ?? 'Follow-up added while editing lead',
                    'metadata' => [
                        'source' => 'lead_edit',
                    ],
                    'assigned_to_user_id' => $lead->assigned_to_user_id ?: auth()->id(),
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

        return back()->with('status', 'Lead updated.');
    }

    public function destroyLead(Request $request, Lead $lead): RedirectResponse
    {
        if (!($request->user()?->isAdmin() ?? false)) {
            abort(403, 'Only administrators can delete leads.');
        }

        $lead->delete();

        return redirect()
            ->route('clinicLeads')
            ->with('status', 'Lead deleted.');
    }

    public function restoreLead(Request $request, int $leadId): RedirectResponse
    {
        if (!($request->user()?->isAdmin() ?? false)) {
            abort(403, 'Only administrators can restore leads.');
        }

        $lead = Lead::withTrashed()->findOrFail($leadId);

        if ($lead->trashed()) {
            $lead->restore();
        }

        return redirect()
            ->route('clinicDeletedLeads')
            ->with('status', 'Lead restored.');
    }

    public function sendWhatsAppMessage(
        Request $request,
        Lead $lead,
        TwilioWhatsAppService $twilioWhatsAppService
    ): RedirectResponse {
        $this->authorizeLeadAccess($request->user(), $lead);

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
        $user = $request->user();
        $validated = $request->validate([
            'tab' => ['nullable', 'string', 'in:today,pending,upcoming'],
        ]);

        $activeTab = (string) ($validated['tab'] ?? 'today');
        $pakistanNow = now('Asia/Karachi');
        $now = $pakistanNow->copy()->utc();
        $todayStart = $pakistanNow->copy()->startOfDay()->utc();
        $todayEnd = $pakistanNow->copy()->endOfDay()->utc();

        $tabScopedPendingQuery = FollowUp::query()
            ->visibleTo($user)
            ->where('status', 'pending')
            ->when($activeTab === 'today', fn (Builder $query): Builder => $query->whereBetween('due_at', [$todayStart, $todayEnd]))
            ->when($activeTab === 'pending', fn (Builder $query): Builder => $query->where('due_at', '<', $now))
            ->when($activeTab === 'upcoming', fn (Builder $query): Builder => $query->where('due_at', '>', $todayEnd));

        $leadIdsSubQuery = (clone $tabScopedPendingQuery)
            ->select('lead_id')
            ->distinct();

        $leads = Lead::query()
            ->with(['contact', 'assignedTo'])
            ->withCount('followUps')
            ->addSelect([
                'next_follow_up_at' => FollowUp::query()
                    ->select('due_at')
                    ->whereColumn('lead_id', 'leads.id')
                    ->where('status', 'pending')
                    ->orderBy('due_at')
                    ->limit(1),
            ])
            ->withMax('followUps as last_follow_up_at', 'due_at')
            ->whereIn('id', $leadIdsSubQuery)
            ->orderByDesc('last_follow_up_at')
            ->orderByDesc('last_activity_at')
            ->get();

        return view('clinic.appointments', [
            'leads' => $leads,
            'activeTab' => $activeTab,
            'todayCount' => FollowUp::query()
                ->visibleTo($user)
                ->where('status', 'pending')
                ->whereBetween('due_at', [$todayStart, $todayEnd])
                ->count(),
            'pendingCount' => FollowUp::query()
                ->visibleTo($user)
                ->where('status', 'pending')
                ->where('due_at', '<', $now)
                ->count(),
            'upcomingCount' => FollowUp::query()
                ->visibleTo($user)
                ->where('status', 'pending')
                ->where('due_at', '>', $todayEnd)
                ->count(),
            'stages' => $this->stageOptions(),
            'procedureOptions' => $this->procedureInterestOptions(),
        ]);
    }

    public function leadFollowUp(Lead $lead): View
    {
        $this->authorizeLeadAccess(request()->user(), $lead);

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
            'genderOptions' => $this->genderOptions(),
            'stages' => $this->stageOptions(),
            'procedureOptions' => $this->procedureInterestOptions(),
        ]);
    }

    public function storeLeadFollowUp(Request $request, Lead $lead): RedirectResponse
    {
        $this->authorizeLeadAccess($request->user(), $lead);

        $methodOptions = array_keys($this->followUpMethodOptions());
        $stageOptions = array_keys($this->followUpFormStageOptions());

        $validated = $request->validate([
            'follow_up_method' => ['required', 'string', Rule::in($methodOptions)],
            'stage' => ['required', 'string', Rule::in($stageOptions)],
        ]);

        $nextStage = $this->normalizeLeadStage((string) $validated['stage']);
        $isClosedStage = $this->isClosedLeadStage($nextStage);
        $stageRequiresFollowUpDetails = $this->stageRequiresFollowUpDetails($nextStage);

        if (
            $nextStage === 'booked'
            && !($request->user()?->hasModulePermission('lead_management', 'mark_booked') ?? false)
        ) {
            abort(403, 'You do not have permission to mark leads as booked.');
        }

        $this->ensureProcedureAttemptedTransitionAllowed($lead, $nextStage);

        $validated = array_merge($validated, $request->validate([
            'next_follow_up_due_at' => [
                Rule::requiredIf($stageRequiresFollowUpDetails),
                'nullable',
                'date',
            ],
            'remarks' => [
                Rule::requiredIf($stageRequiresFollowUpDetails),
                'nullable',
                'string',
                'max:1000',
            ],
        ], [
            'next_follow_up_due_at.required' => 'Next follow-up date and time is required.',
            'remarks.required' => 'Remarks are required.',
        ]));

        $resolvedAt = now();
        $remarks = trim((string) ($validated['remarks'] ?? ''));

        $nextDueAt = $stageRequiresFollowUpDetails && !empty($validated['next_follow_up_due_at'])
            ? $this->parsePakistanDateTimeToUtc((string) $validated['next_follow_up_due_at'])
            : null;

        DB::transaction(function () use ($lead, $validated, $nextStage, $isClosedStage, $stageRequiresFollowUpDetails, $resolvedAt, $nextDueAt, $remarks): void {
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
                'status' => $stageRequiresFollowUpDetails ? 'pending' : 'completed',
                'due_at' => $stageRequiresFollowUpDetails ? $nextDueAt : $resolvedAt,
                'completed_at' => $stageRequiresFollowUpDetails ? null : $resolvedAt,
                'summary' => $remarks !== ''
                    ? $remarks
                    : ($stageRequiresFollowUpDetails
                        ? 'Next follow-up scheduled from follow-up page'
                        : 'Follow-up closed from follow-up page'),
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
            ->with('status', 'Follow-up added.');
    }

    public function updateFollowUp(Request $request, FollowUp $followUp): RedirectResponse
    {
        if (!($request->user()?->isAdmin() ?? false)) {
            abort(403, 'Only administrators can edit follow-ups.');
        }

        if ($followUp->lead === null) {
            abort(404);
        }

        $methodOptions = array_keys($this->followUpEditableMethodOptions());
        $stageOptions = array_keys($this->followUpFormStageOptions());

        $validated = $request->validate([
            'follow_up_method' => ['required', 'string', Rule::in($methodOptions)],
            'stage' => ['required', 'string', Rule::in($stageOptions)],
            'next_follow_up_due_at' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'follow_up_id' => ['nullable', 'integer'],
            'follow_up_edit_submission' => ['nullable', 'boolean'],
        ], [
            'next_follow_up_due_at.required' => 'Next follow-up date and time is required.',
        ]);

        $nextStage = $this->normalizeLeadStage((string) $validated['stage']);
        $isClosedStage = $this->isClosedLeadStage($nextStage);
        $stageRequiresFollowUpDetails = $this->stageRequiresFollowUpDetails($nextStage);
        $nextDueAt = !empty($validated['next_follow_up_due_at'])
            ? $this->parsePakistanDateTimeToUtc((string) $validated['next_follow_up_due_at'])
            : null;
        $remarks = trim((string) ($validated['remarks'] ?? ''));

        if (
            $nextStage === 'booked'
            && !($request->user()?->hasModulePermission('lead_management', 'mark_booked') ?? false)
        ) {
            abort(403, 'You do not have permission to mark leads as booked.');
        }

        $lead = $followUp->lead;

        if ($lead !== null) {
            $this->ensureProcedureAttemptedTransitionAllowed($lead, $nextStage);
        }

        if ($stageRequiresFollowUpDetails && empty($nextDueAt)) {
            return back()
                ->withErrors(['next_follow_up_due_at' => 'Next follow-up date and time is required.'])
                ->withInput();
        }

        DB::transaction(function () use ($followUp, $validated, $nextStage, $isClosedStage, $nextDueAt, $remarks): void {
            $resolvedAt = now();

            $followUp->forceFill([
                'trigger_type' => $validated['follow_up_method'],
                'stage_snapshot' => $nextStage,
                'due_at' => $nextDueAt,
                'summary' => $remarks !== '' ? $remarks : null,
            ])->save();

            $lead = $followUp->lead;

            if ($lead !== null && $followUp->status === 'pending') {
                $lead->forceFill([
                    'stage' => $nextStage,
                    'status' => $isClosedStage ? 'closed' : 'open',
                    'closed_at' => $isClosedStage ? ($lead->closed_at ?? $resolvedAt) : null,
                    'last_activity_at' => $resolvedAt,
                ])->save();
            }
        });

        return redirect()
            ->route('clinicLeadFollowUp', $followUp->lead_id)
            ->with('status', 'Follow-up updated.');
    }

    public function updateFollowUpStatus(
        Request $request,
        FollowUp $followUp,
        TwilioWhatsAppService $twilioWhatsAppService
    ): RedirectResponse
    {
        if ($followUp->lead === null) {
            abort(404);
        }

        $this->authorizeLeadAccess($request->user(), $followUp->lead);

        $validated = $request->validate([
            'stage' => ['required', 'string', Rule::in($this->leadStageValidationOptions())],
            'next_follow_up_due_at' => ['nullable', 'date'],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'send_remarks_to_customer' => ['nullable', 'boolean'],
        ]);

        $nextStage = $this->normalizeLeadStage($validated['stage']);
        $isClosedStage = $this->isClosedLeadStage($nextStage);
        $nextDueAt = !empty($validated['next_follow_up_due_at'])
            ? $this->parsePakistanDateTimeToUtc((string) $validated['next_follow_up_due_at'])
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

        $this->ensureProcedureAttemptedTransitionAllowed($followUp->lead, $nextStage);

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
                    ->with('status', 'Follow-up added. Remarks not sent.');
            }
        }

        if ($isClosedStage) {
            return back()->with('status', 'Follow-up added.');
        }

        return back()->with('status', 'Follow-up added.');
    }

    public function consultations(): View
    {
        $activities = LeadActivity::query()
            ->visibleTo(request()->user())
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
            'procedure_attempted' => 'Procedure Attempted',
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
            'meta' => 'Lead Form',
            'manual' => 'Walk In Lead',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function genderOptions(): array
    {
        return [
            'male' => 'Male',
            'female' => 'Female',
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
    private function followUpEditableMethodOptions(): array
    {
        return [
            'call' => 'Call',
            'whatsapp' => 'WhatsApp',
            'sms' => 'SMS',
            'walkin' => 'Walk In',
            'manual_lead_create' => 'Walk In',
            'manual_stage_update' => 'Manual Stage Update',
            'manual_lead_edit' => 'Lead Edit',
            'manual_follow_up_update' => 'Manual Follow-up',
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
            'procedure_attempted' => 'Procedure Attempted',
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
            'procedure_attempted' => [
                'label' => 'Procedure Attempted',
                'stages' => ['procedure_attempted'],
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
        return in_array($stage, ['booked', 'procedure_attempted', 'not_interested'], true);
    }

    private function stageRequiresFollowUpDetails(string $stage): bool
    {
        return !in_array($stage, ['procedure_attempted', 'not_interested'], true);
    }

    /**
     * @return array<int, string>
     */
    private function leadStageValidationOptions(): array
    {
        return [
            'new',
            'initial',
            'contacted',
            'visit',
            'negotiation',
            'proposal',
            'booked',
            'confirmed',
            'procedure_attempted',
            'not_interested',
        ];
    }

    private function ensureProcedureAttemptedTransitionAllowed(?Lead $lead, string $nextStage): void
    {
        if ($nextStage !== 'procedure_attempted' || $lead === null) {
            return;
        }

        $currentStage = $this->normalizeLeadStage((string) $lead->stage);

        if (in_array($currentStage, ['booked', 'procedure_attempted'], true)) {
            return;
        }

        throw ValidationException::withMessages([
            'stage' => 'Procedure Attempted can only be selected after the lead is booked.',
        ]);
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

    private function parsePakistanDateTimeToUtc(?string $value): ?Carbon
    {
        $resolvedValue = trim((string) $value);

        if ($resolvedValue === '') {
            return null;
        }

        return Carbon::parse($resolvedValue, 'Asia/Karachi')->utc();
    }

    /**
     * @return array{search: string, tab: string, source: string, status: string, date_from: string, date_to: string}
     */
    private function validatedLeadFilters(Request $request): array
    {
        $leadTabs = $this->leadTabs();
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:120'],
            'tab' => ['nullable', 'string', 'max:30'],
            'source' => ['nullable', 'string', 'max:40'],
            'status' => ['nullable', 'string', 'max:20'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);

        $activeTab = (string) ($validated['tab'] ?? 'all');

        if (!array_key_exists($activeTab, $leadTabs)) {
            $activeTab = 'all';
        }

        return [
            'search' => trim((string) ($validated['search'] ?? '')),
            'tab' => $activeTab,
            'source' => trim((string) ($validated['source'] ?? '')),
            'status' => trim((string) ($validated['status'] ?? '')),
            'date_from' => trim((string) ($validated['date_from'] ?? '')),
            'date_to' => trim((string) ($validated['date_to'] ?? '')),
        ];
    }

    private function buildLeadBaseQuery(?User $user, array $filters): Builder
    {
        $dateFrom = $this->parsePakistanDateBoundaryToUtc($filters['date_from'] ?? '', false);
        $dateTo = $this->parsePakistanDateBoundaryToUtc($filters['date_to'] ?? '', true);
        $search = $filters['search'] ?? '';
        $sourceFilter = $filters['source'] ?? '';
        $statusFilter = $filters['status'] ?? '';

        return Lead::query()
            ->visibleTo($user)
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested
                        ->whereHas('contact', function (Builder $contactQuery) use ($search): void {
                            $contactQuery
                                ->where('full_name', 'like', '%'.$search.'%')
                                ->orWhere('phone', 'like', '%'.$search.'%')
                                ->orWhere('normalized_phone', 'like', '%'.$search.'%')
                                ->orWhere('email', 'like', '%'.$search.'%');
                        })
                        ->orWhere('source_platform', 'like', '%'.$search.'%')
                        ->orWhere('stage', 'like', '%'.$search.'%');
                });
            })
            ->when($sourceFilter !== '', fn (Builder $query): Builder => $query->where('source_platform', $sourceFilter))
            ->when($statusFilter !== '', fn (Builder $query): Builder => $query->where('status', $statusFilter))
            ->when($dateFrom !== null, fn (Builder $query): Builder => $query->where('created_at', '>=', $dateFrom))
            ->when($dateTo !== null, fn (Builder $query): Builder => $query->where('created_at', '<=', $dateTo));
    }

    private function applyLeadTabScope(Builder $query, string $activeTab, array $leadTabs): Builder
    {
        $tabStages = $leadTabs[$activeTab]['stages'] ?? [];

        if (empty($tabStages)) {
            return $query;
        }

        return $query->whereIn('stage', $tabStages);
    }

    private function decorateLeadListingQuery(Builder $query): Builder
    {
        return $query
            ->with(['contact', 'assignedTo'])
            ->addSelect([
                'next_follow_up_at' => FollowUp::query()
                    ->select('due_at')
                    ->whereColumn('lead_id', 'leads.id')
                    ->where('status', 'pending')
                    ->orderBy('due_at')
                    ->limit(1),
            ])
            ->withMax('followUps as last_follow_up_at', 'due_at')
            ->orderByRaw("CASE WHEN status = 'open' THEN 0 ELSE 1 END")
            ->orderByDesc('last_follow_up_at')
            ->orderByDesc('last_activity_at');
    }

    private function parsePakistanDateBoundaryToUtc(string $value, bool $endOfDay): ?Carbon
    {
        $resolvedValue = trim($value);

        if ($resolvedValue === '') {
            return null;
        }

        $date = Carbon::parse($resolvedValue, 'Asia/Karachi');

        return $endOfDay ? $date->endOfDay()->utc() : $date->startOfDay()->utc();
    }

    /**
     * @return array<int, int>
     */
    private function parseSelectedLeadIds(string $value): array
    {
        return collect(explode(',', $value))
            ->map(static fn (string $leadId): int => (int) trim($leadId))
            ->filter(static fn (int $leadId): bool => $leadId > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function leadFilterSummary(array $filters, array $leadTabs): string
    {
        $parts = [
            'Tab: '.($leadTabs[$filters['tab']]['label'] ?? 'All Leads'),
        ];

        if (($filters['search'] ?? '') !== '') {
            $parts[] = 'Search: '.$filters['search'];
        }

        if (($filters['source'] ?? '') !== '') {
            $parts[] = 'Source: '.($this->sourceOptions()[$filters['source']] ?? ucfirst(str_replace('_', ' ', (string) $filters['source'])));
        }

        if (($filters['status'] ?? '') !== '') {
            $parts[] = 'Status: '.ucfirst((string) $filters['status']);
        }

        if (($filters['date_from'] ?? '') !== '' || ($filters['date_to'] ?? '') !== '') {
            $from = ($filters['date_from'] ?? '') !== ''
                ? Carbon::parse((string) $filters['date_from'], 'Asia/Karachi')->format('d M Y')
                : 'Any';
            $to = ($filters['date_to'] ?? '') !== ''
                ? Carbon::parse((string) $filters['date_to'], 'Asia/Karachi')->format('d M Y')
                : 'Any';

            $parts[] = 'Created Between: '.$from.' and '.$to;
        }

        return implode(' | ', $parts);
    }

    private function authorizeLeadAccess(?User $user, Lead $lead): void
    {
        if ($lead->isVisibleTo($user)) {
            return;
        }

        abort(403, 'You can only access your own leads.');
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
