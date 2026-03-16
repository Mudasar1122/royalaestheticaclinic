@extends('layout.layout')

@php
    $title = 'CRM';
    $subTitle = 'Add Follow-up';

    $contact = $lead->contact;
    $currentUser = auth()->user();
    $canManageLeadAsAdmin = $currentUser?->isAdmin() ?? false;
    $hasEditLeadErrors = $errors->hasAny([
        'full_name',
        'gender',
        'phone',
        'email',
        'source_platform',
        'procedure_interests',
        'procedure_other',
        'stage',
        'status',
        'follow_up_due_at',
        'follow_up_summary',
    ]);

    $sourceLabel = $sourceOptions[$lead->source_platform] ?? ucfirst(str_replace('_', ' ', (string) $lead->source_platform));
    $currentStage = (string) $lead->stage;
    $editableStage = match ($currentStage) {
        'initial' => 'new',
        'proposal' => 'negotiation',
        'confirmed' => 'booked',
        default => $currentStage,
    };
    $currentStageLabel = match ($currentStage) {
        'new', 'initial' => 'New',
        'contacted' => 'Contacted',
        'visit' => 'Visit',
        'negotiation', 'proposal' => 'Negotiation & Proposal',
        'booked', 'confirmed' => 'Booked',
        'not_interested' => 'Not Interested',
        default => ucfirst(str_replace('_', ' ', $currentStage)),
    };

    $procedureKeys = collect(data_get($lead->meta, 'procedures_of_interest', []))
        ->map(static fn ($value): string => (string) $value)
        ->filter(static fn (string $value): bool => $value !== '')
        ->unique()
        ->values()
        ->all();

    $procedureLabels = collect($procedureKeys)
        ->map(function (string $procedureKey) use ($procedureOptions, $lead): string {
            if ($procedureKey === 'other') {
                $otherValue = trim((string) data_get($lead->meta, 'procedure_other', ''));

                return $otherValue !== '' ? 'Other: '.$otherValue : 'Other';
            }

            return $procedureOptions[$procedureKey] ?? ucfirst(str_replace('_', ' ', $procedureKey));
        })
        ->filter(static fn (string $value): bool => trim($value) !== '')
        ->values()
        ->all();
    $selectedEditProcedures = collect(old('procedure_interests', $procedureKeys))
        ->map(static fn ($value): string => (string) $value)
        ->filter(static fn (string $value): bool => $value !== '')
        ->unique()
        ->values()
        ->all();
    $showEditProcedureOther = in_array('other', $selectedEditProcedures, true);
    $editProcedureOtherValue = old('procedure_other', (string) data_get($lead->meta, 'procedure_other', ''));
    $followUpHistoryColumnCount = $canManageLeadAsAdmin ? 7 : 6;
    $editFollowUpModalTarget = old('follow_up_edit_submission') && old('follow_up_id')
        ? 'editFollowUpModal-'.old('follow_up_id')
        : null;

    $methodLabel = static function (string $value, array $followUpMethods): string {
        if (isset($followUpMethods[$value])) {
            return $followUpMethods[$value];
        }

        return match ($value) {
            'manual_lead_create' => 'Walk In',
            'manual_stage_update' => 'Manual Stage Update',
            'manual_lead_edit' => 'Lead Edit',
            'manual_follow_up_update' => 'Manual Follow-up',
            default => ucfirst(str_replace('_', ' ', $value)),
        };
    };
    $editableFollowUpMethod = static function (string $value): string {
        return match ($value) {
            'manual_lead_create' => 'walkin',
            default => $value,
        };
    };
    $editableFollowUpStage = static function (string $value): string {
        return match ($value) {
            'initial' => 'new',
            'proposal' => 'negotiation',
            'confirmed' => 'booked',
            default => $value,
        };
    };

    $formatPhoneWithGender = static function ($contact): string {
        $phone = trim((string) ($contact?->phone ?? ''));
        $gender = trim((string) ($contact?->gender ?? ''));
        $genderLabel = $gender !== '' ? ucfirst($gender) : '';

        if ($phone !== '' && $genderLabel !== '') {
            return $phone.' / '.$genderLabel;
        }

        if ($phone !== '') {
            return $phone;
        }

        return $genderLabel !== '' ? $genderLabel : '-';
    };
@endphp

@section('content')
    <div class="card border-0 mb-6">
        <div class="card-body p-5 flex items-start justify-between gap-4 flex-wrap">
            <h4 class="mb-0 font-semibold text-neutral-800 dark:text-white">{{ $contact?->full_name ?? 'Unnamed Lead' }}</h4>

            @if ($canManageLeadAsAdmin)
                <div class="followup-action-dropdown" data-action-dropdown>
                    <button
                        type="button"
                        class="followup-action-btn btn btn-outline-primary-600 px-3 py-2 rounded-lg text-xs font-medium inline-flex items-center gap-1"
                        data-action-dropdown-button
                        aria-expanded="false"
                        aria-controls="lead-follow-up-action-menu-{{ $lead->id }}"
                    >
                        Action
                        <iconify-icon icon="heroicons:chevron-down-20-solid" class="text-sm"></iconify-icon>
                    </button>
                    <div
                        id="lead-follow-up-action-menu-{{ $lead->id }}"
                        class="followup-action-menu hidden absolute right-0 mt-2 rounded-xl border border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700 shadow-lg p-2"
                        data-action-dropdown-menu
                    >
                        <button
                            type="button"
                            class="followup-action-item"
                            data-edit-lead-trigger
                            data-modal-target="editLeadModal-{{ $lead->id }}"
                            data-action-menu-close
                        >
                            Edit
                        </button>
                        <form action="{{ route('clinicLeadDestroy', $lead) }}" method="POST" class="followup-action-form" data-delete-lead-form>
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="followup-action-item text-danger-600" data-action-menu-close>
                                Delete
                            </button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div class="lead-followup-tab-wrap mb-6">
        <button type="button" class="lead-followup-tab" data-followup-tab-button="personal-info">
            <iconify-icon icon="heroicons:user-circle" class="lead-followup-tab__icon"></iconify-icon>
            <span>Personal Information</span>
        </button>
        <button type="button" class="lead-followup-tab active" data-followup-tab-button="history">
            <iconify-icon icon="heroicons:list-bullet" class="lead-followup-tab__icon"></iconify-icon>
            <span>Follow-up History</span>
        </button>
    </div>

    <div class="lead-followup-panel" data-followup-tab-panel="personal-info">
        <div class="card border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table bordered-table mb-0">
                        <tbody>
                            <tr>
                                <th class="text-start">Full Name</th>
                                <td class="text-start">{{ $contact?->full_name ?? '-' }}</td>
                                <th class="text-start">Phone</th>
                                <td class="text-start">{{ $formatPhoneWithGender($contact) }}</td>
                            </tr>
                            <tr>
                                <th class="text-start">Email</th>
                                <td class="text-start">{{ $contact?->email ?? '-' }}</td>
                                <th class="text-start">Source Platform</th>
                                <td class="text-start">{{ $sourceLabel }}</td>
                            </tr>
                            <tr>
                                <th class="text-start">Current Stage</th>
                                <td class="text-start">{{ $currentStageLabel }}</td>
                                <th class="text-start">Lead Status</th>
                                <td class="text-start">{{ ucfirst((string) $lead->status) }}</td>
                            </tr>
                            <tr>
                                <th class="text-start align-top">Procedures of Interest</th>
                                <td colspan="3" class="text-start">
                                    @if (!empty($procedureLabels))
                                        <ul class="lead-procedure-list">
                                            @foreach ($procedureLabels as $procedureLabel)
                                                <li>{{ $procedureLabel }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-secondary-light">No procedure selected.</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="lead-followup-panel active" data-followup-tab-panel="history">
        <div class="card border-0 mb-6 lead-followup-form-card">
            <div class="card-header bg-white dark:bg-neutral-700 border-b border-neutral-200 dark:border-neutral-600 p-4 flex items-center justify-between flex-wrap gap-3">
                <h6 class="mb-0 font-semibold text-lg">Follow-up History</h6>
                <button type="button" class="btn btn-primary px-4 py-2 rounded-lg" id="open-followup-form-btn">Add Follow-up</button>
            </div>
            <div class="card-body p-4 hidden" id="followup-form-wrap">
                <form action="{{ route('clinicLeadFollowUpStore', $lead) }}" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    @csrf
                    <div>
                        <label class="form-label">Follow-up Method</label>
                        <select name="follow_up_method" class="form-select rounded-lg" required>
                            @foreach ($followUpMethods as $methodKey => $methodText)
                                <option value="{{ $methodKey }}" @selected(old('follow_up_method') === $methodKey)>{{ $methodText }}</option>
                            @endforeach
                        </select>
                        @error('follow_up_method')
                            <p class="text-danger-600 text-sm mt-1 mb-0">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="form-label">Stage</label>
                        <select name="stage" id="followup-stage-select" class="form-select rounded-lg" required>
                            @foreach ($followUpStages as $stageKey => $stageText)
                                <option value="{{ $stageKey }}" @selected(old('stage') === $stageKey)>{{ $stageText }}</option>
                            @endforeach
                        </select>
                        @error('stage')
                            <p class="text-danger-600 text-sm mt-1 mb-0">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="js-open-stage-field">
                        <label class="form-label">Next Follow-up Date & Time</label>
                        <input
                            type="datetime-local"
                            name="next_follow_up_due_at"
                            id="followup-next-due-at"
                            class="form-control rounded-lg"
                            value="{{ old('next_follow_up_due_at', now('Asia/Karachi')->addDay()->format('Y-m-d\\TH:i')) }}"
                            required
                        >
                        @error('next_follow_up_due_at')
                            <p class="text-danger-600 text-sm mt-1 mb-0">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="js-open-stage-field">
                        <label class="form-label">Remarks</label>
                        <textarea
                            name="remarks"
                            id="followup-remarks"
                            rows="3"
                            class="form-control rounded-lg"
                            placeholder="Write follow-up remarks"
                            required
                        >{{ old('remarks') }}</textarea>
                        @error('remarks')
                            <p class="text-danger-600 text-sm mt-1 mb-0">{{ $message }}</p>
                        @enderror
                    </div>
                    <div class="md:col-span-2 flex items-center gap-2">
                        <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg">Save Follow-up</button>
                        <button type="button" class="btn btn-cancel px-4 py-2 rounded-lg" id="cancel-followup-form-btn">Cancel</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card border-0 lead-followup-history-card">
            <div class="card-body p-4">
                <div class="table-responsive scroll-sm">
                    <table id="lead-followup-history-table" class="table bordered-table mb-0">
                        <thead>
                            <tr>
                                <th>Sr</th>
                                <th>Follower</th>
                                <th>Method</th>
                                <th>Created Date</th>
                                <th>Next Follow-up Date</th>
                                <th>Remarks</th>
                                @if ($canManageLeadAsAdmin)
                                    <th class="text-center">Action</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($followUps as $index => $followUp)
                                @php
                                    $isEditingThisFollowUp = (string) old('follow_up_id') === (string) $followUp->id;
                                    $currentEditableMethod = $editableFollowUpMethod((string) $followUp->trigger_type);
                                    $followUpMethodOptionsForEdit = $followUpMethods;

                                    if (!isset($followUpMethodOptionsForEdit[$currentEditableMethod])) {
                                        $followUpMethodOptionsForEdit = [$currentEditableMethod => $methodLabel((string) $followUp->trigger_type, $followUpMethods)] + $followUpMethodOptionsForEdit;
                                    }

                                    $currentEditableStage = $editableFollowUpStage((string) $followUp->stage_snapshot);
                                    $currentDueAt = $followUp->due_at?->timezone('Asia/Karachi')->format('Y-m-d\\TH:i');
                                    $editFollowUpMethodValue = $isEditingThisFollowUp ? old('follow_up_method') : $currentEditableMethod;
                                    $editFollowUpStageValue = $isEditingThisFollowUp ? old('stage') : $currentEditableStage;
                                    $editFollowUpDueAtValue = $isEditingThisFollowUp ? old('next_follow_up_due_at') : $currentDueAt;
                                    $editFollowUpRemarksValue = $isEditingThisFollowUp ? old('remarks') : (string) ($followUp->summary ?? '');
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $followUp->createdBy?->name ?? 'System' }}</td>
                                    <td>{{ $methodLabel((string) $followUp->trigger_type, $followUpMethods) }}</td>
                                    <td>{{ $followUp->created_at?->timezone('Asia/Karachi')->format('d M Y h:i A') }} PKT</td>
                                    <td>{{ $followUp->due_at?->timezone('Asia/Karachi')->format('d M Y h:i A') }} PKT</td>
                                    <td>{{ trim((string) ($followUp->summary ?? '')) !== '' ? $followUp->summary : '-' }}</td>
                                    @if ($canManageLeadAsAdmin)
                                        <td class="text-center">
                                            <div class="followup-action-dropdown" data-action-dropdown>
                                                <button
                                                    type="button"
                                                    class="followup-action-btn btn btn-outline-primary-600 px-3 py-2 rounded-lg text-xs font-medium inline-flex items-center gap-1"
                                                    data-action-dropdown-button
                                                    aria-expanded="false"
                                                    aria-controls="follow-up-history-action-menu-{{ $followUp->id }}"
                                                >
                                                    Action
                                                    <iconify-icon icon="heroicons:chevron-down-20-solid" class="text-sm"></iconify-icon>
                                                </button>
                                                <div
                                                    id="follow-up-history-action-menu-{{ $followUp->id }}"
                                                    class="followup-action-menu hidden absolute right-0 mt-2 rounded-xl border border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700 shadow-lg p-2"
                                                    data-action-dropdown-menu
                                                >
                                                    <button
                                                        type="button"
                                                        class="followup-action-item"
                                                        data-admin-modal-trigger
                                                        data-modal-target="editFollowUpModal-{{ $followUp->id }}"
                                                        data-action-menu-close
                                                    >
                                                        Edit Follow-up
                                                    </button>
                                                </div>
                                            </div>
                                        </td>
                                    @endif
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ $followUpHistoryColumnCount }}" class="text-center py-10 text-secondary-light">No follow-up history found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if ($canManageLeadAsAdmin)
        <div class="modal fade followup-lead-modal" id="editLeadModal-{{ $lead->id }}" tabindex="-1" aria-hidden="true" style="display: none;" data-lead-modal>
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('clinicLeadUpdate', $lead) }}" method="POST" class="followup-lead-edit-form">
                        @csrf
                        @method('PATCH')
                        <input type="hidden" name="email" value="{{ old('email', $lead->contact?->email) }}">
                        <input type="hidden" name="stage" value="{{ old('stage', $editableStage) }}">
                        <input type="hidden" name="status" value="{{ old('status', $lead->status) }}">
                        <input type="hidden" name="procedure_interests_submitted" value="1">

                        <div class="modal-header followup-lead-modal__header">
                            <div>
                                <h6 class="modal-title mb-1">Edit Lead</h6>
                                <p class="followup-lead-modal__subtitle mb-0">Keep this lead profile clean and update the interested procedures here.</p>
                            </div>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" data-modal-close aria-label="Close"></button>
                        </div>
                        <div class="modal-body followup-lead-modal__body">
                            <section class="followup-lead-section">
                                <div class="followup-lead-section__intro">
                                    <span class="followup-lead-section__eyebrow">Contact Details</span>
                                    <p class="followup-lead-section__copy mb-0">Only the key profile information is shown here for a faster edit flow.</p>
                                </div>

                                <div class="followup-lead-form-grid">
                                    <div class="followup-lead-field followup-lead-field--full">
                                        <label class="followup-lead-field__label">Full Name</label>
                                        <input
                                            type="text"
                                            name="full_name"
                                            value="{{ old('full_name', $lead->contact?->full_name) }}"
                                            class="form-control followup-lead-field__control"
                                            required
                                        >
                                    </div>
                                    <div class="followup-lead-field">
                                        <label class="followup-lead-field__label">Phone Number</label>
                                        <input
                                            type="text"
                                            name="phone"
                                            value="{{ old('phone', $lead->contact?->phone) }}"
                                            class="form-control followup-lead-field__control"
                                        >
                                    </div>
                                    <div class="followup-lead-field">
                                        <label class="followup-lead-field__label">Gender</label>
                                        <div class="gender-choice-group followup-lead-field__control">
                                            @foreach ($genderOptions as $genderKey => $genderLabel)
                                                <label class="gender-choice-option">
                                                    <input
                                                        type="radio"
                                                        name="gender"
                                                        value="{{ $genderKey }}"
                                                        class="gender-choice-option__input"
                                                        @checked(old('gender', $lead->contact?->gender ?? 'female') === $genderKey)
                                                        required
                                                    >
                                                    <span class="gender-choice-option__label">{{ $genderLabel }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="followup-lead-field">
                                        <label class="followup-lead-field__label">Source</label>
                                        <select name="source_platform" class="form-select followup-lead-field__control" required>
                                            @foreach ($sourceOptions as $sourceKey => $sourceText)
                                                <option value="{{ $sourceKey }}" @selected(old('source_platform', $lead->source_platform) === $sourceKey)>{{ $sourceText }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </section>

                            <section class="followup-lead-section followup-lead-section--muted">
                                <div class="followup-lead-section__intro">
                                    <span class="followup-lead-section__eyebrow">Procedure of Interest</span>
                                    <p class="followup-lead-section__copy mb-0">Select one or more services this lead is interested in.</p>
                                </div>

                                <div class="followup-procedure-grid">
                                    @foreach ($procedureOptions as $procedureValue => $procedureLabel)
                                        <label class="followup-procedure-chip">
                                            <input
                                                type="checkbox"
                                                name="procedure_interests[]"
                                                value="{{ $procedureValue }}"
                                                class="followup-procedure-chip__input"
                                                data-edit-procedure-checkbox
                                                @checked(in_array($procedureValue, $selectedEditProcedures, true))
                                            >
                                            <span class="followup-procedure-chip__label">{{ $procedureLabel }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                @error('procedure_interests')
                                    <p class="text-xs text-danger-600 mt-3 mb-0">{{ $message }}</p>
                                @enderror
                                @error('procedure_interests.*')
                                    <p class="text-xs text-danger-600 mt-3 mb-0">{{ $message }}</p>
                                @enderror

                                <div class="followup-lead-field followup-lead-field--full mt-4" data-edit-procedure-other-wrap @if (!$showEditProcedureOther) style="display:none;" @endif>
                                    <label class="followup-lead-field__label">Other Procedure</label>
                                    <input
                                        type="text"
                                        name="procedure_other"
                                        value="{{ $editProcedureOtherValue }}"
                                        class="form-control followup-lead-field__control"
                                        maxlength="255"
                                        placeholder="Write the procedure name"
                                    >
                                    @error('procedure_other')
                                        <p class="text-xs text-danger-600 mt-1 mb-0">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer followup-lead-modal__footer">
                            <button type="button" class="btn btn-cancel followup-lead-modal__cancel-btn" data-bs-dismiss="modal" data-modal-close>Cancel</button>
                            <button type="submit" class="btn btn-primary followup-lead-modal__submit-btn">Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        @foreach ($followUps as $followUp)
            @php
                $isEditingThisFollowUp = (string) old('follow_up_id') === (string) $followUp->id;
                $currentEditableMethod = $editableFollowUpMethod((string) $followUp->trigger_type);
                $followUpMethodOptionsForEdit = $followUpMethods;

                if (!isset($followUpMethodOptionsForEdit[$currentEditableMethod])) {
                    $followUpMethodOptionsForEdit = [$currentEditableMethod => $methodLabel((string) $followUp->trigger_type, $followUpMethods)] + $followUpMethodOptionsForEdit;
                }

                $currentEditableStage = $editableFollowUpStage((string) $followUp->stage_snapshot);
                $currentDueAt = $followUp->due_at?->timezone('Asia/Karachi')->format('Y-m-d\\TH:i');
                $editFollowUpMethodValue = $isEditingThisFollowUp ? old('follow_up_method') : $currentEditableMethod;
                $editFollowUpStageValue = $isEditingThisFollowUp ? old('stage') : $currentEditableStage;
                $editFollowUpDueAtValue = $isEditingThisFollowUp ? old('next_follow_up_due_at') : $currentDueAt;
                $editFollowUpRemarksValue = $isEditingThisFollowUp ? old('remarks') : (string) ($followUp->summary ?? '');
            @endphp
            <div class="modal fade followup-lead-modal followup-history-modal" id="editFollowUpModal-{{ $followUp->id }}" tabindex="-1" aria-hidden="true" style="display: none;" data-lead-modal>
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form action="{{ route('clinicFollowUpUpdate', $followUp) }}" method="POST">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="follow_up_id" value="{{ $followUp->id }}">
                            <input type="hidden" name="follow_up_edit_submission" value="1">

                            <div class="modal-header followup-lead-modal__header">
                                <div>
                                    <h6 class="modal-title mb-1">Edit Follow-up</h6>
                                    <p class="followup-lead-modal__subtitle mb-0">Update the method, stage, schedule, and remarks for this follow-up entry.</p>
                                </div>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" data-modal-close aria-label="Close"></button>
                            </div>

                            <div class="modal-body followup-lead-modal__body">
                                <section class="followup-lead-section">
                                    <div class="followup-lead-form-grid">
                                        <div class="followup-lead-field">
                                            <label class="followup-lead-field__label">Method</label>
                                            <select name="follow_up_method" class="form-select followup-lead-field__control" required>
                                                @foreach ($followUpMethodOptionsForEdit as $methodKey => $methodText)
                                                    <option value="{{ $methodKey }}" @selected($editFollowUpMethodValue === $methodKey)>{{ $methodText }}</option>
                                                @endforeach
                                            </select>
                                            @error('follow_up_method')
                                                @if ($isEditingThisFollowUp)
                                                    <p class="text-xs text-danger-600 mt-1 mb-0">{{ $message }}</p>
                                                @endif
                                            @enderror
                                        </div>

                                        <div class="followup-lead-field">
                                            <label class="followup-lead-field__label">Stage</label>
                                            <select name="stage" class="form-select followup-lead-field__control" data-followup-edit-stage required>
                                                @foreach ($followUpStages as $stageKey => $stageText)
                                                    <option value="{{ $stageKey }}" @selected($editFollowUpStageValue === $stageKey)>{{ $stageText }}</option>
                                                @endforeach
                                            </select>
                                            @error('stage')
                                                @if ($isEditingThisFollowUp)
                                                    <p class="text-xs text-danger-600 mt-1 mb-0">{{ $message }}</p>
                                                @endif
                                            @enderror
                                        </div>

                                        <div class="followup-lead-field">
                                            <label class="followup-lead-field__label">Next Follow-up Date</label>
                                            <input
                                                type="datetime-local"
                                                name="next_follow_up_due_at"
                                                value="{{ $editFollowUpDueAtValue }}"
                                                data-followup-edit-due
                                                class="form-control followup-lead-field__control"
                                                required
                                            >
                                            @error('next_follow_up_due_at')
                                                @if ($isEditingThisFollowUp)
                                                    <p class="text-xs text-danger-600 mt-1 mb-0">{{ $message }}</p>
                                                @endif
                                            @enderror
                                        </div>

                                        <div class="followup-lead-field followup-lead-field--full">
                                            <label class="followup-lead-field__label">Remarks</label>
                                            <textarea
                                                name="remarks"
                                                rows="4"
                                                class="form-control followup-lead-field__control followup-lead-field__textarea"
                                                maxlength="1000"
                                                placeholder="Write follow-up remarks"
                                            >{{ $editFollowUpRemarksValue }}</textarea>
                                            @error('remarks')
                                                @if ($isEditingThisFollowUp)
                                                    <p class="text-xs text-danger-600 mt-1 mb-0">{{ $message }}</p>
                                                @endif
                                            @enderror
                                        </div>
                                    </div>
                                </section>
                            </div>

                            <div class="modal-footer followup-lead-modal__footer">
                                <button type="button" class="btn btn-cancel followup-lead-modal__cancel-btn" data-bs-dismiss="modal" data-modal-close>Cancel</button>
                                <button type="submit" class="btn btn-primary followup-lead-modal__submit-btn">Update Follow-up</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endforeach
    @endif

    <style>
        .gender-choice-group {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            min-height: 46px;
            padding: 10px 12px;
            border: 1px solid #d4d7dd;
            border-radius: 10px;
            background: #fff;
        }

        .gender-choice-option {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin: 0;
            cursor: pointer;
            color: #111827;
            font-size: 0.95rem;
        }

        .gender-choice-option__input {
            width: 16px;
            height: 16px;
            accent-color: rgb(var(--ra-primary-rgb, 190 133 0));
        }

        .lead-followup-tab-wrap {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            border: 1px solid #d4d7dd;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
        }

        .lead-followup-tab {
            border: 0;
            border-right: 1px solid #d4d7dd;
            min-height: 62px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: #fff;
            font-size: 1.1rem;
            font-weight: 600;
            color: #344054;
        }

        .lead-followup-tab:last-child {
            border-right: 0;
        }

        .lead-followup-tab.active {
            color: rgb(var(--ra-primary-rgb, 190 133 0));
            background: rgb(var(--ra-primary-rgb, 190 133 0) / 0.12);
            box-shadow: inset 0 3px 0 rgb(var(--ra-primary-rgb, 190 133 0));
        }

        .lead-followup-tab__icon {
            font-size: 18px;
        }

        .lead-followup-panel {
            display: none;
        }

        .lead-followup-panel.active {
            display: block;
        }

        .lead-procedure-list {
            margin: 0;
            padding-left: 18px;
        }

        .lead-procedure-list li {
            margin-bottom: 4px;
        }

        .followup-action-dropdown {
            position: relative;
            display: inline-block;
            z-index: 1;
        }

        .followup-action-dropdown.is-open {
            z-index: 2500;
        }

        .followup-action-btn {
            min-width: 96px;
            justify-content: center;
            white-space: nowrap;
        }

        .followup-action-menu {
            position: absolute;
            top: calc(100% + 8px);
            right: 0;
            z-index: 2510;
            min-width: 220px;
            width: max-content !important;
            padding: 0.35rem;
            overflow: hidden;
        }

        .followup-action-menu.open-up {
            top: auto;
            bottom: calc(100% + 8px);
        }

        .followup-action-menu > * + * {
            border-top: 1px solid #eef0f3;
        }

        .followup-action-form {
            margin: 0;
        }

        .followup-action-item {
            display: block;
            width: 100%;
            border: 0;
            background: transparent;
            text-align: left;
            white-space: nowrap;
            padding: 10px 12px;
            font-size: 14px;
            color: #475467;
            border-radius: 8px;
        }

        .followup-action-item:hover {
            background: #f2f4f7;
            color: #101828;
        }

        .followup-lead-modal {
            position: fixed;
            inset: 0;
            z-index: 2050;
            overflow-x: hidden;
            overflow-y: auto;
            padding: 20px 12px;
            background: rgba(15, 23, 42, 0.44);
        }

        .followup-lead-modal:not(.show) {
            display: none !important;
        }

        .followup-lead-modal.show {
            display: block !important;
        }

        .followup-lead-modal .modal-dialog {
            margin: 1.75rem auto;
            max-width: min(760px, calc(100vw - 28px));
        }

        .followup-lead-modal .modal-content {
            border: 0;
            border-radius: 18px;
            overflow: hidden;
            background: #ffffff;
            box-shadow: 0 24px 60px rgba(15, 23, 42, 0.22);
        }

        .followup-lead-modal__header {
            padding: 24px 26px 18px;
            border-bottom: 1px solid rgba(190, 133, 0, 0.14);
            align-items: flex-start;
            background: linear-gradient(180deg, rgba(190, 133, 0, 0.12) 0%, rgba(255, 255, 255, 0.98) 72%);
        }

        .followup-lead-modal__subtitle {
            color: #667085;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .followup-lead-modal__body {
            padding: 24px 26px 26px;
            background: #ffffff;
        }

        .followup-lead-form-grid {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 16px;
        }

        .followup-lead-section + .followup-lead-section {
            margin-top: 18px;
        }

        .followup-lead-section {
            padding: 18px;
            border: 1px solid #eceff3;
            border-radius: 18px;
            background: #ffffff;
        }

        .followup-lead-section--muted {
            background: linear-gradient(180deg, rgba(247, 248, 250, 0.96) 0%, #ffffff 100%);
        }

        .followup-lead-section__intro {
            margin-bottom: 16px;
        }

        .followup-lead-section__eyebrow {
            display: inline-flex;
            align-items: center;
            padding: 6px 10px;
            margin-bottom: 10px;
            border-radius: 999px;
            background: rgba(190, 133, 0, 0.12);
            color: rgb(var(--ra-primary-rgb, 190 133 0));
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.04em;
            text-transform: uppercase;
        }

        .followup-lead-section__copy {
            color: #667085;
            font-size: 0.92rem;
            line-height: 1.55;
        }

        .followup-lead-field {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .followup-lead-field--full {
            grid-column: 1 / -1;
        }

        .followup-lead-field__label {
            color: #344054;
            font-size: 0.92rem;
            font-weight: 600;
            margin: 0;
        }

        .followup-lead-field__control {
            min-height: 52px;
            border-radius: 14px;
            border: 1px solid #d8dde5;
            background: #fbfcfe;
            box-shadow: none;
            color: #111827;
        }

        .followup-lead-field__control:focus {
            border-color: rgb(var(--ra-primary-rgb, 190 133 0));
            box-shadow: 0 0 0 4px rgb(var(--ra-primary-rgb, 190 133 0) / 0.12);
            background: #fff;
        }

        .followup-lead-field__textarea {
            min-height: 132px;
            resize: vertical;
            padding-top: 14px;
        }

        .followup-procedure-grid {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .followup-procedure-chip {
            position: relative;
            margin: 0;
            cursor: pointer;
        }

        .followup-procedure-chip__input {
            position: absolute;
            opacity: 0;
            pointer-events: none;
        }

        .followup-procedure-chip__label {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 42px;
            padding: 10px 14px;
            border-radius: 999px;
            border: 1px solid #d8dde5;
            background: #ffffff;
            color: #344054;
            font-size: 0.9rem;
            font-weight: 500;
            line-height: 1.35;
            transition: all 0.18s ease;
        }

        .followup-procedure-chip__input:checked + .followup-procedure-chip__label {
            border-color: rgb(var(--ra-primary-rgb, 190 133 0));
            background: rgb(var(--ra-primary-rgb, 190 133 0) / 0.12);
            color: #7a5200;
            box-shadow: inset 0 0 0 1px rgb(var(--ra-primary-rgb, 190 133 0) / 0.12);
        }

        .followup-procedure-chip__input:focus + .followup-procedure-chip__label {
            box-shadow: 0 0 0 4px rgb(var(--ra-primary-rgb, 190 133 0) / 0.12);
        }

        .followup-lead-modal__footer {
            padding: 0 26px 26px;
            border-top: 0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            background: #ffffff;
        }

        .followup-lead-modal__cancel-btn,
        .followup-lead-modal__submit-btn {
            min-width: 128px;
            min-height: 48px;
            border-radius: 14px;
            font-size: 0.95rem;
            font-weight: 600;
        }

        .followup-lead-modal__submit-btn {
            box-shadow: 0 14px 30px rgb(var(--ra-primary-rgb, 190 133 0) / 0.24);
        }

        body.followup-modal-open {
            overflow: hidden;
        }

        .lead-followup-history-card .datatable-wrapper .datatable-top {
            padding: 0 0 14px;
            margin-bottom: 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .lead-followup-history-card .datatable-wrapper .datatable-bottom {
            padding: 14px 0 0;
            margin-top: 0;
            border-top: 1px solid #e5e7eb;
        }

        .lead-followup-history-card .datatable-wrapper .datatable-search .datatable-input {
            border-radius: 10px;
            border-color: #d4d7dd;
        }

        .lead-followup-history-card .datatable-wrapper .datatable-search .datatable-input:focus {
            border-color: rgb(var(--ra-primary-rgb, 190 133 0));
        }

        @media (max-width: 991px) {
            .lead-followup-tab-wrap {
                grid-template-columns: 1fr;
            }

            .lead-followup-tab {
                border-right: 0;
                border-bottom: 1px solid #d4d7dd;
            }

            .lead-followup-tab:last-child {
                border-bottom: 0;
            }

            .followup-lead-form-grid {
                grid-template-columns: 1fr;
            }

            .followup-lead-modal__header,
            .followup-lead-modal__body,
            .followup-lead-modal__footer {
                padding-left: 18px;
                padding-right: 18px;
            }

            .followup-lead-section {
                padding: 16px;
            }

            .followup-lead-modal__footer {
                flex-direction: column-reverse;
            }

            .followup-lead-modal__cancel-btn,
            .followup-lead-modal__submit-btn {
                width: 100%;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabButtons = Array.from(document.querySelectorAll('[data-followup-tab-button]'));
            const tabPanels = Array.from(document.querySelectorAll('[data-followup-tab-panel]'));
            const formWrap = document.getElementById('followup-form-wrap');
            const openFormBtn = document.getElementById('open-followup-form-btn');
            const cancelFormBtn = document.getElementById('cancel-followup-form-btn');
            const stageSelect = document.getElementById('followup-stage-select');
            const nextDueAtInput = document.getElementById('followup-next-due-at');
            const remarksInput = document.getElementById('followup-remarks');
            const openStageFields = Array.from(document.querySelectorAll('.js-open-stage-field'));
            const hasEditLeadErrors = @json($hasEditLeadErrors);
            const editFollowUpModalTarget = @json($editFollowUpModalTarget);
            const actionDropdowns = Array.from(document.querySelectorAll('[data-action-dropdown]'));
            const editLeadTriggers = Array.from(document.querySelectorAll('[data-edit-lead-trigger]'));
            const leadModals = Array.from(document.querySelectorAll('[data-lead-modal]'));
            const editProcedureCheckboxes = Array.from(document.querySelectorAll('[data-edit-procedure-checkbox]'));
            const editProcedureOtherWrap = document.querySelector('[data-edit-procedure-other-wrap]');
            let activeLeadModal = null;

            const closeAllDropdowns = function (except = null) {
                actionDropdowns.forEach((dropdown) => {
                    if (except && dropdown === except) {
                        return;
                    }

                    const menu = dropdown.querySelector('[data-action-dropdown-menu]');
                    const button = dropdown.querySelector('[data-action-dropdown-button]');

                    if (menu) {
                        menu.classList.add('hidden');
                        if (window.royalUi && typeof window.royalUi.resetActionDropdown === 'function') {
                            window.royalUi.resetActionDropdown(menu);
                        } else {
                            menu.classList.remove('open-up');
                        }
                    }

                    dropdown.classList.remove('is-open');

                    if (button) {
                        button.setAttribute('aria-expanded', 'false');
                    }
                });
            };

            const placeMenu = function (button, menu) {
                if (window.royalUi && typeof window.royalUi.placeActionDropdown === 'function') {
                    window.royalUi.placeActionDropdown(button, menu);
                    return;
                }
            };

            const getBootstrapModalInstance = function (modal) {
                if (!modal || typeof window.bootstrap === 'undefined' || typeof window.bootstrap.Modal === 'undefined') {
                    return null;
                }

                if (typeof window.bootstrap.Modal.getOrCreateInstance === 'function') {
                    return window.bootstrap.Modal.getOrCreateInstance(modal);
                }

                return new window.bootstrap.Modal(modal);
            };

            const openLeadModal = function (modal) {
                if (!modal) {
                    return;
                }

                activeLeadModal = modal;
                closeAllDropdowns();

                const bootstrapModal = getBootstrapModalInstance(modal);

                if (bootstrapModal) {
                    bootstrapModal.show();
                    return;
                }

                modal.classList.add('show');
                modal.style.display = 'block';
                modal.removeAttribute('aria-hidden');
                modal.setAttribute('aria-modal', 'true');
                document.body.classList.add('followup-modal-open');
            };

            const closeLeadModal = function (modal) {
                if (!modal) {
                    return;
                }

                const bootstrapModal = getBootstrapModalInstance(modal);

                if (bootstrapModal) {
                    bootstrapModal.hide();
                    return;
                }

                modal.classList.remove('show');
                modal.style.display = 'none';
                modal.setAttribute('aria-hidden', 'true');
                modal.removeAttribute('aria-modal');

                if (activeLeadModal === modal) {
                    activeLeadModal = null;
                }

                document.body.classList.remove('followup-modal-open');
            };

            const syncEditProcedureOther = function () {
                if (!editProcedureOtherWrap) {
                    return;
                }

                const otherCheckbox = editProcedureCheckboxes.find((checkbox) => checkbox.value === 'other');
                const otherInput = editProcedureOtherWrap.querySelector('input[name="procedure_other"]');
                const showOtherInput = Boolean(otherCheckbox && otherCheckbox.checked);

                editProcedureOtherWrap.style.display = showOtherInput ? '' : 'none';

                if (otherInput) {
                    otherInput.disabled = !showOtherInput;
                }
            };

            const syncFollowUpEditStageFields = function (modal) {
                if (!modal) {
                    return;
                }

                const stageSelectInput = modal.querySelector('[data-followup-edit-stage]');
                const dueAtInput = modal.querySelector('[data-followup-edit-due]');

                if (!stageSelectInput || !dueAtInput) {
                    return;
                }

                const isClosedStage = ['booked', 'not_interested'].includes(stageSelectInput.value);

                dueAtInput.required = !isClosedStage;
                dueAtInput.disabled = isClosedStage;
            };

            editLeadTriggers.forEach((trigger) => {
                trigger.addEventListener('click', function (event) {
                    event.preventDefault();
                    event.stopPropagation();

                    const modalId = trigger.getAttribute('data-modal-target');
                    const modal = modalId ? document.getElementById(modalId) : null;

                    openLeadModal(modal);
                });
            });

            leadModals.forEach((modal) => {
                modal.querySelectorAll('[data-modal-close], [data-bs-dismiss="modal"]').forEach((closeControl) => {
                    closeControl.addEventListener('click', function (event) {
                        event.preventDefault();
                        event.stopPropagation();
                        closeLeadModal(modal);
                    });
                });

                modal.addEventListener('click', function (event) {
                    if (event.target === modal) {
                        closeLeadModal(modal);
                    }
                });

                modal.addEventListener('hidden.bs.modal', function () {
                    if (activeLeadModal === modal) {
                        activeLeadModal = null;
                    }

                    document.body.classList.remove('followup-modal-open');
                });
            });

            editProcedureCheckboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', syncEditProcedureOther);
            });

            syncEditProcedureOther();

            document.querySelectorAll('[data-followup-edit-stage]').forEach((stageSelectInput) => {
                const modal = stageSelectInput.closest('[data-lead-modal]');

                stageSelectInput.addEventListener('change', function () {
                    syncFollowUpEditStageFields(modal);
                });

                syncFollowUpEditStageFields(modal);
            });

            if (hasEditLeadErrors) {
                openLeadModal(document.getElementById('editLeadModal-{{ $lead->id }}'));
            }

            if (editFollowUpModalTarget) {
                openLeadModal(document.getElementById(editFollowUpModalTarget));
            }

            document.addEventListener('click', function (event) {
                const target = event.target;

                if (!(target instanceof Element)) {
                    return;
                }

                const modalTrigger = target.closest('[data-admin-modal-trigger]');

                if (modalTrigger) {
                    event.preventDefault();
                    event.stopPropagation();

                    const modalId = modalTrigger.getAttribute('data-modal-target');
                    const modal = modalId ? document.getElementById(modalId) : null;

                    openLeadModal(modal);
                    return;
                }

                const button = target.closest('[data-action-dropdown-button]');

                if (button) {
                    event.preventDefault();
                    event.stopPropagation();

                    const dropdown = button.closest('[data-action-dropdown]');
                    const menu = dropdown ? dropdown.querySelector('[data-action-dropdown-menu]') : null;

                    if (!dropdown || !menu) {
                        return;
                    }

                    const shouldOpen = menu.classList.contains('hidden');
                    closeAllDropdowns(dropdown);

                    if (shouldOpen) {
                        dropdown.classList.add('is-open');
                        menu.classList.remove('hidden');
                        placeMenu(button, menu);
                        button.setAttribute('aria-expanded', 'true');
                    } else {
                        menu.classList.add('hidden');
                        if (window.royalUi && typeof window.royalUi.resetActionDropdown === 'function') {
                            window.royalUi.resetActionDropdown(menu);
                        } else {
                            menu.classList.remove('open-up');
                        }
                        dropdown.classList.remove('is-open');
                        button.setAttribute('aria-expanded', 'false');
                    }

                    return;
                }

                if (target.closest('[data-action-menu-close]')) {
                    closeAllDropdowns();
                    return;
                }

                if (!target.closest('[data-action-dropdown]')) {
                    closeAllDropdowns();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeAllDropdowns();

                    if (activeLeadModal) {
                        closeLeadModal(activeLeadModal);
                    }
                }
            });

            window.addEventListener('resize', function () {
                closeAllDropdowns();
            });

            window.addEventListener('scroll', function () {
                closeAllDropdowns();
            }, true);

            document.querySelectorAll('[data-delete-lead-form]').forEach(function (form) {
                form.addEventListener('submit', function (event) {
                    if (!window.confirm('Delete this lead?')) {
                        event.preventDefault();
                    }
                });
            });

            const setActiveTab = function (target) {
                tabButtons.forEach((button) => {
                    button.classList.toggle('active', button.dataset.followupTabButton === target);
                });

                tabPanels.forEach((panel) => {
                    panel.classList.toggle('active', panel.dataset.followupTabPanel === target);
                });
            };

            tabButtons.forEach((button) => {
                button.addEventListener('click', function () {
                    const target = button.dataset.followupTabButton || 'personal-info';
                    setActiveTab(target);
                });
            });

            if (openFormBtn && formWrap) {
                openFormBtn.addEventListener('click', function () {
                    formWrap.classList.remove('hidden');
                });
            }

            if (cancelFormBtn && formWrap) {
                cancelFormBtn.addEventListener('click', function () {
                    formWrap.classList.add('hidden');
                });
            }

            const syncStageFields = function () {
                if (!stageSelect) {
                    return;
                }

                const isClosedStage = ['booked', 'not_interested'].includes(stageSelect.value);

                openStageFields.forEach((fieldWrap) => {
                    fieldWrap.classList.toggle('hidden', isClosedStage);
                });

                if (nextDueAtInput) {
                    nextDueAtInput.required = !isClosedStage;
                    nextDueAtInput.disabled = isClosedStage;
                }

                if (remarksInput) {
                    remarksInput.required = !isClosedStage;
                    remarksInput.disabled = isClosedStage;
                }
            };

            if (stageSelect) {
                stageSelect.addEventListener('change', syncStageFields);
                syncStageFields();
            }

            if ({{ $errors->any() ? 'true' : 'false' }}) {
                setActiveTab('history');
                if (formWrap) {
                    formWrap.classList.remove('hidden');
                }
            }

            if (document.getElementById('lead-followup-history-table') && typeof simpleDatatables !== 'undefined' && typeof simpleDatatables.DataTable !== 'undefined') {
                const followupHistoryTable = new simpleDatatables.DataTable('#lead-followup-history-table', {
                    searchable: true,
                    fixedHeight: false,
                    perPage: 10,
                    perPageSelect: [10, 25, 50, 100],
                    labels: {
                        placeholder: 'Search...',
                        perPage: 'Rows per page',
                        noRows: 'No follow-up history found.',
                        info: 'Showing {start} to {end} of {rows} entries',
                    },
                });

                if (window.royalUi && typeof window.royalUi.enableDatatableAllOption === 'function') {
                    window.royalUi.enableDatatableAllOption(followupHistoryTable);
                }
            }
        });
    </script>
@endsection
