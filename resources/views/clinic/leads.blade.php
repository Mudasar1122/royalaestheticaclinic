@extends('layout.layout')

@php
    $title = 'CRM';
    $subTitle = 'Leads';
    $canManageFollowups = auth()->user()?->hasModulePermission('lead_management', 'manage_followups') ?? false;
    $canMarkBooked = auth()->user()?->hasModulePermission('lead_management', 'mark_booked') ?? false;

    $stageBadgeClasses = [
        'new' => 'bg-warning-100 dark:bg-warning-600/20 text-warning-600 dark:text-warning-300',
        'contacted' => 'bg-info-100 dark:bg-info-600/20 text-info-600 dark:text-info-300',
        'visit' => 'bg-primary-100 dark:bg-primary-600/20 text-primary-600 dark:text-primary-300',
        'negotiation' => 'bg-purple-100 dark:bg-purple-600/20 text-purple-600 dark:text-purple-300',
        'booked' => 'bg-success-100 dark:bg-success-600/20 text-success-600 dark:text-success-300',
        'procedure_attempted' => 'bg-info-100 dark:bg-info-600/20 text-info-600 dark:text-info-300',
    ];

    $normalizeStage = static fn (string $stage): string => match ($stage) {
        'initial' => 'new',
        'proposal' => 'negotiation',
        'confirmed' => 'booked',
        default => $stage,
    };

    $readableStage = static function (string $stage) use ($normalizeStage, $stages): string {
        $normalized = $normalizeStage($stage);

        return $stages[$normalized] ?? ucfirst(str_replace('_', ' ', $normalized));
    };
    $readableSource = static function (string $source) use ($sources): string {
        return $sources[$source] ?? ucfirst(str_replace('_', ' ', $source));
    };

    $formatPhoneOnly = static function ($contact): string {
        $phone = trim((string) ($contact?->phone ?? ''));

        if ($phone !== '') {
            return $phone;
        }

        return '-';
    };
@endphp

@section('content')
    @php
        $leadPageError = $errors->first('whatsapp')
            ?: $errors->first('export')
            ?: $errors->first('date_from')
            ?: $errors->first('date_to');
    @endphp

    @if ($leadPageError !== '')
        <div class="alert alert-danger px-4 py-3 rounded-lg mb-4">
            {{ $leadPageError }}
        </div>
    @endif

    <div class="followup-tab-wrap mb-6">
        @foreach ($leadTabs as $tabKey => $tabConfig)
            @php
                $tabQuery = array_filter(
                    [
                        'tab' => $tabKey,
                        'search' => $filters['search'] !== '' ? $filters['search'] : null,
                        'source' => $filters['source'] !== '' ? $filters['source'] : null,
                        'status' => $filters['status'] !== '' ? $filters['status'] : null,
                        'date_from' => $filters['date_from'] !== '' ? $filters['date_from'] : null,
                        'date_to' => $filters['date_to'] !== '' ? $filters['date_to'] : null,
                    ],
                    static fn ($value) => $value !== null && $value !== ''
                );
            @endphp
            <a
                href="{{ route('clinicLeads', $tabQuery) }}"
                class="followup-tab {{ $activeTab === $tabKey ? 'active' : '' }}"
            >
                <div class="followup-tab__content">
                    <span class="followup-tab__label">{{ $tabConfig['label'] }}</span>
                    <span class="followup-tab__badge">{{ number_format((int) ($tabCounts[$tabKey] ?? 0)) }}</span>
                </div>
            </a>
        @endforeach
    </div>

    <div class="card border-0 followup-grid-card">
        <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700">
            <h6 class="mb-0 font-semibold text-lg">Leads</h6>
        </div>

        <div class="lead-filter-wrap">
            <form method="GET" action="{{ route('clinicLeads') }}" class="lead-filter-form">
                <input type="hidden" name="tab" value="{{ $activeTab }}">

                <div class="lead-filter-form__field lead-filter-form__search">
                    <label class="lead-filter-form__label" for="lead-search">Search</label>
                    <input
                        id="lead-search"
                        type="text"
                        name="search"
                        value="{{ $filters['search'] }}"
                        class="form-control rounded-lg"
                        placeholder="Search name, phone, email"
                    >
                </div>

                <div class="lead-filter-form__field lead-filter-form__source">
                    <label class="lead-filter-form__label" for="lead-source">Source</label>
                    <select id="lead-source" name="source" class="form-select rounded-lg">
                        <option value="">All Sources</option>
                        @foreach ($sources as $sourceKey => $sourceLabel)
                            <option value="{{ $sourceKey }}" @selected($filters['source'] === $sourceKey)>{{ $sourceLabel }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="lead-filter-form__field lead-filter-form__status">
                    <label class="lead-filter-form__label" for="lead-status">Status</label>
                    <select id="lead-status" name="status" class="form-select rounded-lg">
                        <option value="">All Status</option>
                        <option value="open" @selected($filters['status'] === 'open')>Open</option>
                        <option value="closed" @selected($filters['status'] === 'closed')>Closed</option>
                    </select>
                </div>

                <div class="lead-filter-form__field lead-filter-form__date">
                    <label class="lead-filter-form__label" for="lead-date-from">Created From</label>
                    <input
                        id="lead-date-from"
                        type="date"
                        name="date_from"
                        value="{{ $filters['date_from'] }}"
                        class="form-control rounded-lg"
                    >
                </div>

                <div class="lead-filter-form__field lead-filter-form__date">
                    <label class="lead-filter-form__label" for="lead-date-to">Created To</label>
                    <input
                        id="lead-date-to"
                        type="date"
                        name="date_to"
                        value="{{ $filters['date_to'] }}"
                        class="form-control rounded-lg"
                    >
                </div>

                <div class="lead-filter-form__actions">
                    <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg text-sm">Apply Filter</button>
                    <a href="{{ route('clinicLeads', ['tab' => $activeTab]) }}" class="btn btn-outline-primary-600 px-4 py-2 rounded-lg text-sm">Reset</a>
                </div>
            </form>

            <form method="POST" action="{{ route('clinicLeadExport') }}" class="lead-export-form" id="lead-export-form">
                @csrf
                <input type="hidden" name="tab" value="{{ $activeTab }}">
                <input type="hidden" name="search" value="{{ $filters['search'] }}">
                <input type="hidden" name="source" value="{{ $filters['source'] }}">
                <input type="hidden" name="status" value="{{ $filters['status'] }}">
                <input type="hidden" name="date_from" value="{{ $filters['date_from'] }}">
                <input type="hidden" name="date_to" value="{{ $filters['date_to'] }}">
                <input type="hidden" name="lead_ids" value="" id="lead-export-ids">
                <input type="hidden" name="scope" value="all" id="lead-export-scope">
                <input type="hidden" name="format" value="excel" id="lead-export-format">

                <div class="lead-export-form__meta">
                    <span class="lead-export-form__selected"><span id="lead-selected-count">0</span> lead(s) selected</span>
                    <span class="lead-export-form__note">All export buttons use the active tab and current filters.</span>
                </div>

                <div class="lead-export-form__actions">
                    <button type="button" class="btn btn-outline-primary-600 px-4 py-2 rounded-lg text-sm" data-export-trigger data-export-scope="selected" data-export-format="excel" disabled>
                        Selected Excel
                    </button>
                    <button type="button" class="btn btn-outline-primary-600 px-4 py-2 rounded-lg text-sm" data-export-trigger data-export-scope="selected" data-export-format="pdf" disabled>
                        Selected PDF
                    </button>
                    <button type="button" class="btn btn-primary px-4 py-2 rounded-lg text-sm" data-export-trigger data-export-scope="all" data-export-format="excel" @disabled($leads->isEmpty())>
                        All Excel
                    </button>
                    <button type="button" class="btn btn-primary px-4 py-2 rounded-lg text-sm" data-export-trigger data-export-scope="all" data-export-format="pdf" @disabled($leads->isEmpty())>
                        All PDF
                    </button>
                </div>
            </form>
        </div>

        <div class="card-body p-0">
            <div class="table-responsive scroll-sm">
                <table id="clinic-leads-table" class="table bordered-table mb-0">
                    <thead>
                        <tr>
                            <th class="lead-select-cell">
                                <input type="checkbox" class="form-check-input lead-select-all" data-select-all aria-label="Select all visible leads">
                            </th>
                            <th>Name</th>
                            <th>Phone No</th>
                            <th>Source</th>
                            <th>Created</th>
                            <th>Procedure</th>
                            <th>Stage</th>
                            <th>Next Follow-up</th>
                            <th>User</th>
                            <th class="text-center">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($leads as $lead)
                            @php
                                $normalizedStage = $normalizeStage((string) $lead->stage);
                                $stageLabel = $readableStage((string) $lead->stage);

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
                                $nextFollowUpAt = $lead->next_follow_up_at
                                    ? \Illuminate\Support\Carbon::parse((string) $lead->next_follow_up_at)->timezone('Asia/Karachi')
                                    : null;
                            @endphp
                            <tr>
                                <td class="lead-select-cell">
                                    <input
                                        type="checkbox"
                                        class="form-check-input lead-select-checkbox"
                                        data-lead-id="{{ $lead->id }}"
                                        aria-label="Select {{ $lead->contact?->full_name ?? 'lead' }} for export"
                                    >
                                </td>
                                <td>
                                    <span class="lead-sort-value">{{ trim((string) ($lead->contact?->full_name ?? 'Unnamed Lead')) }}</span>
                                    @if ($canManageFollowups)
                                        <a href="{{ route('clinicLeadFollowUp', $lead) }}" class="font-medium text-neutral-700 dark:text-neutral-100 hover:text-primary-600">
                                            {{ $lead->contact?->full_name ?? 'Unnamed Lead' }}
                                        </a>
                                    @else
                                        <span class="font-medium text-neutral-700 dark:text-neutral-100">{{ $lead->contact?->full_name ?? 'Unnamed Lead' }}</span>
                                    @endif
                                </td>
                                <td>{{ $formatPhoneOnly($lead->contact) }}</td>
                                <td>{{ $readableSource((string) $lead->source_platform) }}</td>
                                <td>
                                    <span class="lead-sort-value">{{ ($lead->created_at?->format('YmdHis') ?? '00000000000000').'-'.str_pad((string) $lead->id, 10, '0', STR_PAD_LEFT) }}</span>
                                    @if ($lead->created_at)
                                        {{ $lead->created_at->timezone('Asia/Karachi')->format('d M Y h:i A') }} PKT
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if (!empty($procedureLabels))
                                        <ul class="procedure-stack">
                                            @foreach ($procedureLabels as $procedureLabel)
                                                <li>{{ $procedureLabel }}</li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <span class="text-sm text-secondary-light">Not selected</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="followup-stage-pill px-3 py-1 rounded-full text-xs font-semibold {{ $stageBadgeClasses[$normalizedStage] ?? 'bg-neutral-200 dark:bg-neutral-600 text-neutral-700 dark:text-neutral-200' }}">
                                        {{ $stageLabel }}
                                    </span>
                                </td>
                                <td>
                                    <span class="lead-sort-value">{{ $nextFollowUpAt?->format('YmdHis') ?? '99999999999999' }}</span>
                                    @if ($nextFollowUpAt)
                                        <div class="followup-date-stack">
                                            <span>{{ $nextFollowUpAt->format('d M Y') }}</span>
                                            <span class="followup-date-stack__meta">{{ $nextFollowUpAt->format('h:i A') }} PKT</span>
                                        </div>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>{{ $lead->assignedTo?->name ?? 'Unassigned' }}</td>
                                <td class="text-center">
                                    @php
                                        $showAddFollowUp = $canManageFollowups;
                                        $showMarkBooked = $canMarkBooked && !in_array($normalizedStage, ['booked', 'procedure_attempted'], true);
                                    @endphp
                                    @if ($showAddFollowUp || $showMarkBooked)
                                        <div class="followup-action-dropdown" data-action-dropdown>
                                            <button
                                                type="button"
                                                class="followup-action-btn btn btn-outline-primary-600 px-3 py-2 rounded-lg text-xs font-medium inline-flex items-center gap-1"
                                                data-action-dropdown-button
                                                aria-expanded="false"
                                                aria-controls="lead-action-menu-{{ $lead->id }}"
                                            >
                                                Action
                                                <iconify-icon icon="heroicons:chevron-down-20-solid" class="text-sm"></iconify-icon>
                                            </button>
                                            <div
                                                id="lead-action-menu-{{ $lead->id }}"
                                                class="followup-action-menu hidden absolute right-0 mt-2 rounded-xl border border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700 shadow-lg p-2"
                                                data-action-dropdown-menu
                                            >
                                                @if ($showAddFollowUp)
                                                    <a
                                                        href="{{ route('clinicLeadFollowUp', $lead) }}"
                                                        class="followup-action-item"
                                                        data-action-menu-close
                                                    >
                                                        Add Follow-up
                                                    </a>
                                                @endif
                                                @if ($showMarkBooked)
                                                <form action="{{ route('clinicLeadStageUpdate', $lead) }}" method="POST" class="followup-action-form">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="stage" value="booked">
                                                    <button type="submit" class="followup-action-item" data-action-menu-close>
                                                        Mark as Booked
                                                    </button>
                                                </form>
                                                @endif
                                            </div>
                                        </div>
                                    @else
                                        <span class="text-secondary-light">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center py-10 text-secondary-light">No leads found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    @foreach ($leads as $lead)
        <div class="modal fade" id="whatsAppModal-{{ $lead->id }}" tabindex="-1" aria-hidden="true" style="display: none;">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form action="{{ route('clinicLeadWhatsAppSend', $lead) }}" method="POST">
                        @csrf
                        <div class="modal-header">
                            <h6 class="modal-title">Send WhatsApp Message</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="form-label">Recipient</label>
                                <input
                                    type="text"
                                    class="form-control rounded-lg"
                                    value="{{ $lead->contact?->phone ?? 'No phone on lead contact' }}"
                                    disabled
                                >
                                <p class="text-xs text-secondary-light mt-1 mb-0">If a WhatsApp identity exists, it will be used automatically.</p>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message</label>
                                <textarea name="message" class="form-control rounded-lg" rows="4" maxlength="4096" required>{{ old('message') }}</textarea>
                            </div>
                            <p class="text-xs text-secondary-light mb-0">
                                Message will be sent via Twilio WhatsApp.
                            </p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-cancel px-4 py-2 rounded-lg" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="modal fade" id="editLeadModal-{{ $lead->id }}" tabindex="-1" aria-hidden="true" style="display: none;">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <form action="{{ route('clinicLeadUpdate', $lead) }}" method="POST">
                        @csrf
                        @method('PATCH')
                        <div class="modal-header">
                            <h6 class="modal-title">Edit Lead</h6>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <div class="grid grid-cols-12 gap-4">
                                <div class="col-span-12 md:col-span-6">
                                    <label class="form-label">Full Name</label>
                                    <input type="text" name="full_name" value="{{ $lead->contact?->full_name }}" class="form-control rounded-lg" required>
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="form-label">Gender</label>
                                    <div class="gender-choice-group">
                                        @foreach ($genderOptions as $genderKey => $genderLabel)
                                            <label class="gender-choice-option">
                                                <input
                                                    type="radio"
                                                    name="gender"
                                                    value="{{ $genderKey }}"
                                                    class="gender-choice-option__input"
                                                    @checked(($lead->contact?->gender ?? 'female') === $genderKey)
                                                    required
                                                >
                                                <span class="gender-choice-option__label">{{ $genderLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="form-label">Phone</label>
                                    <input type="text" name="phone" value="{{ $lead->contact?->phone }}" class="form-control rounded-lg">
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" value="{{ $lead->contact?->email }}" class="form-control rounded-lg">
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="form-label">Source</label>
                                    <select name="source_platform" class="form-select rounded-lg" required>
                                        @foreach ($sources as $sourceKey => $sourceLabel)
                                            <option value="{{ $sourceKey }}" @selected($lead->source_platform === $sourceKey)>{{ $sourceLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="form-label">Stage</label>
                                    <select name="stage" class="form-select rounded-lg" required>
                                        @foreach ($stages as $stageKey => $stageText)
                                            <option value="{{ $stageKey }}" @selected($normalizeStage((string) $lead->stage) === $stageKey)>{{ $stageText }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="form-label">Status</label>
                                    <select name="status" class="form-select rounded-lg" required>
                                        <option value="open" @selected($lead->status === 'open')>Open</option>
                                        <option value="closed" @selected($lead->status === 'closed')>Closed</option>
                                    </select>
                                    <p class="text-xs text-secondary-light mt-1 mb-0">Booked and Procedure Attempted stages will automatically set status to Closed.</p>
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="form-label">Next Follow-up (Optional)</label>
                                    <input type="datetime-local" name="follow_up_due_at" class="form-control rounded-lg">
                                </div>
                                <div class="col-span-12 md:col-span-6">
                                    <label class="form-label">Follow-up Summary (Optional)</label>
                                    <input type="text" name="follow_up_summary" class="form-control rounded-lg" maxlength="255">
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-cancel px-4 py-2 rounded-lg" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg">Update Lead</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endforeach

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

        .followup-tab-wrap {
            display: flex;
            flex-wrap: nowrap;
            gap: 0;
            border: 1px solid #d4d7dd;
            border-radius: 10px;
            overflow: hidden;
            background: #fff;
            -webkit-overflow-scrolling: touch;
        }

        .followup-tab {
            border-right: 1px solid #d4d7dd;
            flex: 1 1 0;
            min-width: 0;
            min-height: 66px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #fff;
            text-decoration: none;
            transition: background 0.2s ease, box-shadow 0.2s ease;
        }

        .followup-tab:last-child {
            border-right: 0;
        }

        .followup-tab.active {
            background: rgb(var(--ra-primary-rgb, 190 133 0) / 0.12);
            box-shadow: inset 0 3px 0 rgb(var(--ra-primary-rgb, 190 133 0));
        }

        .followup-tab__content {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 10px 12px;
            text-align: center;
            flex-wrap: nowrap;
            white-space: nowrap;
            min-width: 0;
        }

        .followup-tab__label {
            font-size: 1rem;
            color: #344054;
            font-weight: 600;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .followup-tab.active .followup-tab__label {
            color: rgb(var(--ra-primary-rgb, 190 133 0));
        }

        .followup-tab__badge {
            font-size: 0.8rem;
            font-weight: 700;
            border-radius: 999px;
            padding: 4px 10px;
            line-height: 1;
            background: rgb(var(--ra-primary-rgb, 190 133 0) / 0.12);
            color: rgb(var(--ra-primary-rgb, 190 133 0));
        }

        .followup-tab.active .followup-tab__badge {
            background: rgb(var(--ra-primary-rgb, 190 133 0));
            color: #fff;
        }

        .lead-filter-wrap {
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            padding: 16px;
            background: #fff;
            display: grid;
            gap: 14px;
        }

        .lead-filter-form {
            display: grid;
            grid-template-columns: minmax(240px, 1.45fr) repeat(4, minmax(150px, 1fr));
            gap: 12px;
            align-items: end;
        }

        .lead-filter-form__field {
            display: grid;
            gap: 6px;
            min-width: 0;
        }

        .lead-filter-form__label {
            font-size: 0.78rem;
            font-weight: 600;
            color: #475467;
        }

        .lead-filter-form__search {
            grid-column: span 1;
        }

        .lead-filter-form__actions {
            display: flex;
            align-items: center;
            gap: 8px;
            grid-column: 1 / -1;
            flex-wrap: wrap;
            justify-content: flex-end;
            padding-top: 2px;
        }

        .lead-filter-form__actions .btn {
            min-width: 116px;
        }

        .lead-export-form {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 12px;
            padding-top: 14px;
            border-top: 1px solid #e5e7eb;
        }

        .lead-export-form__meta {
            display: grid;
            gap: 4px;
        }

        .lead-export-form__selected {
            font-size: 0.95rem;
            font-weight: 600;
            color: #111827;
        }

        .lead-export-form__note {
            font-size: 0.78rem;
            color: #667085;
        }

        .lead-export-form__actions {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-end;
        }

        .lead-export-form__actions .btn {
            min-width: 128px;
        }

        .followup-grid-card .datatable-wrapper .datatable-top {
            padding: 12px 16px;
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            margin-bottom: 0;
            display: flex;
            justify-content: flex-end;
            gap: 12px;
        }

        .followup-grid-card .datatable-wrapper .datatable-bottom {
            padding: 12px 16px;
            border-top: 1px solid #e5e7eb;
            margin-top: 0;
        }

        .followup-grid-card .datatable-wrapper .datatable-top .datatable-search {
            display: none;
        }

        .followup-grid-card .datatable-wrapper .datatable-search .datatable-input {
            border: 1px solid #d4d7dd;
            border-radius: 8px;
            padding: 10px 12px;
        }

        .followup-grid-card .datatable-wrapper .datatable-search .datatable-input:focus {
            border-color: rgb(var(--ra-primary-rgb, 190 133 0));
            box-shadow: none;
        }

        #clinic-leads-table {
            width: 100%;
        }

        .followup-grid-card .table {
            min-width: 100% !important;
            table-layout: fixed;
        }

        #clinic-leads-table td {
            white-space: normal !important;
            word-break: normal !important;
            overflow-wrap: anywhere;
            vertical-align: top;
        }

        #clinic-leads-table th {
            white-space: nowrap !important;
            word-break: normal !important;
            overflow-wrap: normal;
            vertical-align: middle;
            font-size: 0.8rem;
            letter-spacing: 0.02em;
        }

        #clinic-leads-table th,
        #clinic-leads-table td {
            padding-inline: 12px;
        }

        #clinic-leads-table th:nth-child(1),
        #clinic-leads-table td:nth-child(1) {
            width: 2.5%;
            min-width: 42px;
            text-align: center;
        }

        #clinic-leads-table th:nth-child(2),
        #clinic-leads-table td:nth-child(2) {
            width: 14%;
        }

        #clinic-leads-table th:nth-child(3),
        #clinic-leads-table td:nth-child(3) {
            width: 12%;
            white-space: nowrap !important;
        }

        #clinic-leads-table th:nth-child(4),
        #clinic-leads-table td:nth-child(4) {
            width: 10%;
        }

        #clinic-leads-table th:nth-child(5),
        #clinic-leads-table td:nth-child(5) {
            width: 12%;
        }

        #clinic-leads-table th:nth-child(6),
        #clinic-leads-table td:nth-child(6) {
            width: 20%;
            text-align: left;
        }

        #clinic-leads-table th:nth-child(7),
        #clinic-leads-table td:nth-child(7) {
            width: 9%;
        }

        #clinic-leads-table th:nth-child(8),
        #clinic-leads-table td:nth-child(8) {
            width: 11%;
        }

        #clinic-leads-table th:nth-child(9),
        #clinic-leads-table td:nth-child(9) {
            width: 8%;
        }

        #clinic-leads-table th:nth-child(10),
        #clinic-leads-table td:nth-child(10) {
            width: 8%;
            white-space: nowrap !important;
        }

        .lead-select-cell {
            text-align: center !important;
            vertical-align: middle !important;
        }

        .lead-select-checkbox,
        .lead-select-all {
            width: 16px;
            height: 16px;
            accent-color: rgb(var(--ra-primary-rgb, 190 133 0));
        }

        .lead-sort-value {
            display: none;
        }

        .followup-date-stack {
            display: grid;
            gap: 2px;
        }

        .followup-date-stack__meta {
            font-size: 0.75rem;
            color: #667085;
            white-space: nowrap;
        }

        .followup-stage-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            max-width: 100%;
            text-align: center;
            line-height: 1.25;
            white-space: normal;
            word-break: normal !important;
            overflow-wrap: normal;
        }

        .procedure-stack {
            margin: 0;
            padding-left: 0;
            list-style: none;
            display: grid;
            gap: 4px;
        }

        .procedure-stack li {
            font-size: 0.88rem;
            color: #344054;
            line-height: 1.35;
            position: relative;
            padding-left: 12px;
        }

        .procedure-stack li::before {
            content: '\2022';
            position: absolute;
            left: 0;
            color: #475467;
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
            min-width: 240px;
            width: max-content !important;
            max-height: 340px;
            overflow-y: auto;
            padding: 0.35rem;
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

        @media (max-width: 1400px) {
            .lead-filter-form {
                grid-template-columns: repeat(3, minmax(0, 1fr));
            }

            .lead-filter-form__search {
                grid-column: span 3;
            }
        }

        @media (max-width: 1200px) {
            .lead-filter-form {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .lead-filter-form__search {
                grid-column: span 2;
            }

            .lead-export-form {
                grid-template-columns: 1fr;
            }

            .lead-export-form__actions {
                justify-content: flex-start;
            }
        }

        @media (max-width: 991px) {
            .followup-tab {
                flex: 0 0 220px;
            }

            .followup-tab-wrap {
                overflow-x: auto;
                overflow-y: hidden;
            }

            .lead-filter-form {
                grid-template-columns: 1fr;
            }

            .lead-filter-form__search {
                grid-column: span 1;
            }

            .lead-filter-form__source,
            .lead-filter-form__status {
                width: 100%;
            }

            .lead-filter-form__actions {
                flex-wrap: wrap;
                justify-content: flex-start;
            }

            .lead-export-form {
                grid-template-columns: 1fr;
                align-items: stretch;
            }

            .lead-export-form__actions {
                width: 100%;
                justify-content: flex-start;
            }

            .followup-grid-card .table-responsive {
                overflow-x: auto;
            }

            .followup-grid-card .table {
                min-width: 1120px !important;
            }
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const table = document.getElementById('clinic-leads-table');
            const exportForm = document.getElementById('lead-export-form');
            const exportIdsInput = document.getElementById('lead-export-ids');
            const exportScopeInput = document.getElementById('lead-export-scope');
            const exportFormatInput = document.getElementById('lead-export-format');
            const selectedCount = document.getElementById('lead-selected-count');
            const selectedExportButtons = Array.from(document.querySelectorAll('[data-export-trigger][data-export-scope="selected"]'));
            const selectedLeadIds = new Set();

            const syncLeadSelectionUi = function () {
                const leadCheckboxes = Array.from(document.querySelectorAll('.lead-select-checkbox'));
                const allToggle = document.querySelector('[data-select-all]');
                const normalizedSelectedIds = Array.from(selectedLeadIds)
                    .filter((value) => value !== '')
                    .sort((left, right) => Number(left) - Number(right));

                leadCheckboxes.forEach((checkbox) => {
                    if (!(checkbox instanceof HTMLInputElement)) {
                        return;
                    }

                    const leadId = String(checkbox.dataset.leadId || '');
                    checkbox.checked = leadId !== '' && selectedLeadIds.has(leadId);
                });

                if (selectedCount) {
                    selectedCount.textContent = String(normalizedSelectedIds.length);
                }

                if (exportIdsInput instanceof HTMLInputElement) {
                    exportIdsInput.value = normalizedSelectedIds.join(',');
                }

                selectedExportButtons.forEach((button) => {
                    if (button instanceof HTMLButtonElement) {
                        button.disabled = normalizedSelectedIds.length === 0;
                    }
                });

                if (allToggle instanceof HTMLInputElement) {
                    const visibleIds = leadCheckboxes
                        .map((checkbox) => String(checkbox.getAttribute('data-lead-id') || ''))
                        .filter((value) => value !== '');
                    const visibleSelectedCount = visibleIds.filter((leadId) => selectedLeadIds.has(leadId)).length;

                    allToggle.disabled = visibleIds.length === 0;
                    allToggle.checked = visibleIds.length > 0 && visibleSelectedCount === visibleIds.length;
                    allToggle.indeterminate = visibleSelectedCount > 0 && visibleSelectedCount < visibleIds.length;
                }
            };

            if (table && typeof simpleDatatables !== 'undefined' && typeof simpleDatatables.DataTable !== 'undefined') {
                const leadsTable = new simpleDatatables.DataTable('#clinic-leads-table', {
                    searchable: false,
                    fixedHeight: false,
                    perPage: 10,
                    perPageSelect: [10, 25, 50, 100],
                    columns: [
                        { select: [0, 2, 3, 5, 6, 8, 9], sortable: false, searchable: false },
                        { select: [1, 4, 7], sortable: true, searchable: false },
                    ],
                    labels: {
                        placeholder: 'Search...',
                        perPage: 'Rows per page',
                        noRows: 'No leads found.',
                        info: 'Showing {start} to {end} of {rows} entries',
                    },
                });

                if (window.royalUi && typeof window.royalUi.enableDatatableAllOption === 'function') {
                    window.royalUi.enableDatatableAllOption(leadsTable);
                }
            }

            if (table) {
                const tableObserver = new MutationObserver(function () {
                    syncLeadSelectionUi();
                });

                tableObserver.observe(table, {
                    childList: true,
                    subtree: true,
                });
            }

            const getDropdowns = function () {
                return Array.from(document.querySelectorAll('[data-action-dropdown]'));
            };

            const closeAll = function (except = null) {
                getDropdowns().forEach((dropdown) => {
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

            document.addEventListener('click', function (event) {
                const target = event.target;

                if (!(target instanceof Element)) {
                    return;
                }

                const exportButton = target.closest('[data-export-trigger]');

                if (exportButton instanceof HTMLButtonElement) {
                    event.preventDefault();

                    if (!exportForm || !(exportScopeInput instanceof HTMLInputElement) || !(exportFormatInput instanceof HTMLInputElement)) {
                        return;
                    }

                    exportScopeInput.value = String(exportButton.getAttribute('data-export-scope') || 'all');
                    exportFormatInput.value = String(exportButton.getAttribute('data-export-format') || 'excel');
                    syncLeadSelectionUi();
                    exportForm.submit();

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
                    closeAll(dropdown);

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
                    closeAll();
                    return;
                }

                if (!target.closest('[data-action-dropdown]')) {
                    closeAll();
                }
            });

            document.addEventListener('change', function (event) {
                const target = event.target;

                if (!(target instanceof HTMLInputElement)) {
                    return;
                }

                if (target.matches('.lead-select-checkbox')) {
                    const leadId = String(target.dataset.leadId || '');

                    if (leadId === '') {
                        return;
                    }

                    if (target.checked) {
                        selectedLeadIds.add(leadId);
                    } else {
                        selectedLeadIds.delete(leadId);
                    }

                    syncLeadSelectionUi();

                    return;
                }

                if (target.matches('[data-select-all]')) {
                    document.querySelectorAll('.lead-select-checkbox').forEach((checkbox) => {
                        if (!(checkbox instanceof HTMLInputElement)) {
                            return;
                        }

                        const leadId = String(checkbox.dataset.leadId || '');

                        if (leadId === '') {
                            return;
                        }

                        if (target.checked) {
                            selectedLeadIds.add(leadId);
                        } else {
                            selectedLeadIds.delete(leadId);
                        }
                    });

                    syncLeadSelectionUi();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closeAll();
                }
            });

            window.addEventListener('resize', function () {
                closeAll();
            });

            window.addEventListener('scroll', function () {
                closeAll();
            }, true);

            syncLeadSelectionUi();
        });
    </script>
@endsection
