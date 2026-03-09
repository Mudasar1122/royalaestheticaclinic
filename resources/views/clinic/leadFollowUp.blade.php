@extends('layout.layout')

@php
    $title = 'CRM';
    $subTitle = 'Add Follow-up';

    $contact = $lead->contact;

    $sourceLabel = $sourceOptions[$lead->source_platform] ?? ucfirst(str_replace('_', ' ', (string) $lead->source_platform));
    $currentStage = (string) $lead->stage;
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
@endphp

@section('content')
    <div class="card border-0 mb-6">
        <div class="card-body p-5">
            <h4 class="mb-0 font-semibold text-neutral-800 dark:text-white">{{ $contact?->full_name ?? 'Unnamed Lead' }}</h4>
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
                                <td class="text-start">{{ $contact?->phone ?? '-' }}</td>
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
                        <button type="button" class="btn btn-light px-4 py-2 rounded-lg" id="cancel-followup-form-btn">Cancel</button>
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
                                <th>Next Follow-up Date</th>
                                <th>Remarks</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($followUps as $index => $followUp)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $followUp->createdBy?->name ?? 'System' }}</td>
                                    <td>{{ $methodLabel((string) $followUp->trigger_type, $followUpMethods) }}</td>
                                    <td>{{ $followUp->due_at?->timezone('Asia/Karachi')->format('d M Y h:i A') }} PKT</td>
                                    <td>{{ trim((string) ($followUp->summary ?? '')) !== '' ? $followUp->summary : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-10 text-secondary-light">No follow-up history found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <style>
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

        .swal-theme-confirm-btn {
            background: rgb(var(--ra-primary-rgb, 190 133 0)) !important;
            color: #fff !important;
            border: 0 !important;
            border-radius: 10px !important;
            padding: 10px 24px !important;
            min-width: 96px !important;
            font-size: 14px !important;
            font-weight: 600 !important;
            line-height: 1.2 !important;
        }

        .swal-theme-confirm-btn:focus {
            box-shadow: none !important;
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
        }
    </style>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            const flashStatus = @json(session('status'));
            const firstError = @json($errors->first());

            const primaryButton = document.querySelector('.btn.btn-primary');
            const primaryColor = primaryButton
                ? window.getComputedStyle(primaryButton).backgroundColor
                : 'rgb(190, 133, 0)';

            if (flashStatus && window.Swal) {
                window.Swal.fire({
                    icon: 'success',
                    title: flashStatus,
                    iconColor: primaryColor,
                    confirmButtonText: 'OK',
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: 'swal-theme-confirm-btn'
                    }
                });
            }

            if (!flashStatus && firstError && window.Swal) {
                window.Swal.fire({
                    icon: 'error',
                    title: firstError,
                    iconColor: primaryColor,
                    confirmButtonText: 'OK',
                    buttonsStyling: false,
                    customClass: {
                        confirmButton: 'swal-theme-confirm-btn'
                    }
                });
            }

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
                new simpleDatatables.DataTable('#lead-followup-history-table', {
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
            }
        });
    </script>
@endsection
