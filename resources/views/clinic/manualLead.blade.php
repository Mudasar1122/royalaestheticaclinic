@extends('layout.layout')

@php
    $title = 'CRM';
    $subTitle = 'Create New Lead';

    $selectedProcedures = collect(old('procedure_interests', []))
        ->map(static fn ($value): string => (string) $value)
        ->filter(static fn (string $value): bool => $value !== '')
        ->values()
        ->all();

    $hasOtherSelected = in_array('other', $selectedProcedures, true);

    $sourceOrder = ['manual', 'whatsapp', 'facebook', 'meta', 'google_business', 'instagram', 'tiktok'];
    $orderedSources = collect($sourceOrder)
        ->filter(static fn (string $key): bool => isset($sources[$key]))
        ->mapWithKeys(static fn (string $key): array => [$key => $sources[$key]])
        ->all();

    $oldPhone = (string) old('phone', '');
    $oldPhoneDigits = preg_replace('/\D+/', '', $oldPhone) ?? '';

    if (str_starts_with($oldPhoneDigits, '0092')) {
        $oldPhoneDigits = substr($oldPhoneDigits, 2);
    }

    if (str_starts_with($oldPhoneDigits, '92')) {
        $oldPhoneDigits = substr($oldPhoneDigits, 2);
    }

    $phoneLocalDefault = ltrim($oldPhoneDigits, '0');
    $selectedGender = (string) old('gender', 'female');
@endphp

@section('content')
    <div class="grid grid-cols-12 gap-6">
        <div class="col-span-12">
            <div class="card border-0">
                <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700 p-4">
                    <h6 class="text-lg font-semibold mb-1">Create New Lead</h6>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('clinicManualLeadStore') }}" class="grid grid-cols-12 gap-4">
                        @csrf
                        <input type="hidden" name="stage" value="new">

                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label @error('full_name') text-danger-600 @enderror">Full Name <span class="text-danger-600">*</span></label>
                            <input
                                type="text"
                                name="full_name"
                                value="{{ old('full_name') }}"
                                class="form-control rounded-lg @error('full_name') border-danger-500 @enderror"
                                placeholder="Enter full name"
                                maxlength="120"
                                required
                            >
                            @error('full_name')
                                <p class="text-xs text-danger-600 mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label @error('gender') text-danger-600 @enderror">Gender <span class="text-danger-600">*</span></label>
                            <div class="gender-choice-group @error('gender') gender-choice-group--error @enderror">
                                @foreach ($genderOptions as $genderValue => $genderLabel)
                                    <label class="gender-choice-option">
                                        <input
                                            type="radio"
                                            name="gender"
                                            value="{{ $genderValue }}"
                                            class="gender-choice-option__input"
                                            @checked($selectedGender === $genderValue)
                                            required
                                        >
                                        <span class="gender-choice-option__label">{{ $genderLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('gender')
                                <p class="text-xs text-danger-600 mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label @error('phone') text-danger-600 @enderror">Phone No <span class="text-danger-600">*</span></label>
                            <input type="hidden" name="phone" value="{{ old('phone') }}" data-phone-hidden>
                            <div class="flex">
                                <span class="inline-flex items-center px-3 rounded-l-lg border border-r-0 border-neutral-300 bg-neutral-100 text-neutral-700 text-sm">+92</span>
                                <input
                                    type="tel"
                                    name="phone_local"
                                    value="{{ $phoneLocalDefault }}"
                                    class="form-control rounded-l-none @error('phone') border-danger-500 @enderror"
                                    placeholder="3XXXXXXXXX"
                                    maxlength="12"
                                    inputmode="numeric"
                                    autocomplete="off"
                                    data-phone-local
                                    required
                                >
                            </div>
                            @error('phone')
                                <p class="text-xs text-danger-600 mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label @error('email') text-danger-600 @enderror">Email (Optional)</label>
                            <input
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                class="form-control rounded-lg @error('email') border-danger-500 @enderror"
                                placeholder="name@example.com"
                                maxlength="120"
                            >
                            @error('email')
                                <p class="text-xs text-danger-600 mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label @error('source_platform') text-danger-600 @enderror">Source Platform <span class="text-danger-600">*</span></label>
                            <select name="source_platform" class="form-select rounded-lg @error('source_platform') border-danger-500 @enderror" required>
                                <option value="">Select source</option>
                                @foreach ($orderedSources as $sourceKey => $sourceLabel)
                                    <option value="{{ $sourceKey }}" @selected(old('source_platform', 'manual') === $sourceKey)>{{ $sourceLabel }}</option>
                                @endforeach
                            </select>
                            @error('source_platform')
                                <p class="text-xs text-danger-600 mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label @error('follow_up_due_at') text-danger-600 @enderror">Next Follow-up Date & Time (PKT) <span class="text-danger-600">*</span></label>
                            <input
                                type="datetime-local"
                                name="follow_up_due_at"
                                value="{{ old('follow_up_due_at', now('Asia/Karachi')->format('Y-m-d\TH:i')) }}"
                                class="form-control rounded-lg @error('follow_up_due_at') border-danger-500 @enderror"
                                required
                            >
                            @error('follow_up_due_at')
                                <p class="text-xs text-danger-600 mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-12 md:col-span-6">
                            <label class="form-label @error('procedure_interests') text-danger-600 @enderror">Procedures of Interest</label>
                            <div class="procedure-picker" data-procedure-picker>
                                <button type="button" class="procedure-picker__toggle" data-procedure-toggle aria-expanded="false">
                                    <span class="procedure-picker__summary" data-procedure-summary>Select procedures</span>
                                    <iconify-icon icon="heroicons:chevron-down-20-solid" class="text-lg"></iconify-icon>
                                </button>

                                <div class="procedure-picker__panel hidden" data-procedure-panel>
                                    <div class="mb-3">
                                        <input
                                            type="text"
                                            class="form-control rounded-lg"
                                            placeholder="Search procedures"
                                            data-procedure-search
                                        >
                                    </div>
                                    <div class="procedure-picker__list" data-procedure-list>
                                        @foreach ($procedureOptions as $procedureValue => $procedureLabel)
                                            <label class="procedure-picker__item" data-procedure-item>
                                                <input
                                                    type="checkbox"
                                                    name="procedure_interests[]"
                                                    value="{{ $procedureValue }}"
                                                    class="form-check-input mt-0"
                                                    data-procedure-checkbox
                                                    @checked(in_array($procedureValue, $selectedProcedures, true))
                                                >
                                                <span>{{ $procedureLabel }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                            @error('procedure_interests')
                                <p class="text-xs text-danger-600 mt-1 mb-0">{{ $message }}</p>
                            @enderror
                            @error('procedure_interests.*')
                                <p class="text-xs text-danger-600 mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-12" data-procedure-other-wrap @if (!$hasOtherSelected) style="display:none;" @endif>
                            <label class="form-label @error('procedure_other') text-danger-600 @enderror">Other</label>
                            <input
                                type="text"
                                name="procedure_other"
                                value="{{ old('procedure_other') }}"
                                class="form-control rounded-lg @error('procedure_other') border-danger-500 @enderror"
                                placeholder="Write other procedure"
                                maxlength="255"
                            >
                            @error('procedure_other')
                                <p class="text-xs text-danger-600 mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-12">
                            <label class="form-label @error('remarks') text-danger-600 @enderror">Remarks</label>
                            <textarea
                                name="remarks"
                                class="form-control rounded-lg @error('remarks') border-danger-500 @enderror"
                                rows="4"
                                maxlength="2000"
                                placeholder="Add follow-up remarks"
                            >{{ old('remarks') }}</textarea>
                            @error('remarks')
                                <p class="text-xs text-danger-600 mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="col-span-12 flex flex-wrap gap-2 pt-1">
                            <button type="submit" class="btn btn-primary px-4 py-2 rounded-lg">Create New Lead</button>
                            <a href="{{ route('clinicLeads') }}" class="btn btn-cancel px-4 py-2 rounded-lg">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

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

        .gender-choice-group--error {
            border-color: #ef4444;
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
            accent-color: var(--crm-primary-color, #465fff);
        }

        .procedure-picker {
            position: relative;
        }

        .procedure-picker__toggle {
            width: 100%;
            border: 1px solid #d4d7dd;
            border-radius: 10px;
            min-height: 46px;
            background: #fff;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            text-align: left;
        }

        .procedure-picker__summary {
            color: #111827;
            font-size: 1rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .procedure-picker__panel {
            position: absolute;
            top: calc(100% + 8px);
            left: 0;
            right: 0;
            background: #fff;
            border: 1px solid #d4d7dd;
            border-radius: 12px;
            box-shadow: 0 12px 28px rgba(15, 23, 42, 0.12);
            padding: 12px;
            z-index: 40;
        }

        .procedure-picker__list {
            max-height: 260px;
            overflow-y: auto;
            display: grid;
            gap: 6px;
            padding-right: 4px;
        }

        .procedure-picker__item {
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            margin: 0;
            font-size: 0.95rem;
            color: #111827;
        }

        .procedure-picker__item:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }

        .procedure-picker__item input[type="checkbox"] {
            width: 16px;
            height: 16px;
            accent-color: var(--crm-primary-color, #465fff);
            border-color: var(--crm-primary-color, #465fff);
        }

    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const primaryButton = document.querySelector('.btn.btn-primary');
            const primaryColor = primaryButton
                ? window.getComputedStyle(primaryButton).backgroundColor
                : '#465fff';
            document.documentElement.style.setProperty('--crm-primary-color', primaryColor);

            const leadForm = document.querySelector('form[action="{{ route('clinicManualLeadStore') }}"]');
            const phoneHidden = document.querySelector('[data-phone-hidden]');
            const phoneLocal = document.querySelector('[data-phone-local]');

            if (leadForm && phoneHidden && phoneLocal) {
                const sanitizePhone = function (raw) {
                    return raw
                        .replace(/\D+/g, '')
                        .replace(/^0+/, '');
                };

                const syncPhone = function () {
                    const cleaned = sanitizePhone(phoneLocal.value);

                    if (phoneLocal.value !== cleaned) {
                        phoneLocal.value = cleaned;
                    }

                    phoneHidden.value = cleaned !== '' ? '+92' + cleaned : '';
                };

                phoneLocal.addEventListener('keydown', function (event) {
                    if (
                        event.key === '0'
                        && phoneLocal.selectionStart === 0
                        && phoneLocal.selectionEnd === 0
                        && phoneLocal.value.length === 0
                    ) {
                        event.preventDefault();
                    }
                });

                phoneLocal.addEventListener('input', syncPhone);
                leadForm.addEventListener('submit', syncPhone);
                syncPhone();
            }

            const picker = document.querySelector('[data-procedure-picker]');

            if (!picker) {
                return;
            }

            const toggleButton = picker.querySelector('[data-procedure-toggle]');
            const panel = picker.querySelector('[data-procedure-panel]');
            const searchInput = picker.querySelector('[data-procedure-search]');
            const summary = picker.querySelector('[data-procedure-summary]');
            const items = Array.from(picker.querySelectorAll('[data-procedure-item]'));
            const checkboxes = Array.from(picker.querySelectorAll('[data-procedure-checkbox]'));
            const otherWrap = document.querySelector('[data-procedure-other-wrap]');

            if (!toggleButton || !panel || !summary || !searchInput || !items.length || !checkboxes.length) {
                return;
            }

            const selectedLabels = function () {
                return checkboxes
                    .filter((checkbox) => checkbox.checked)
                    .map((checkbox) => {
                        const label = checkbox.closest('[data-procedure-item]');
                        return label ? label.textContent.trim() : '';
                    })
                    .filter((text) => text !== '');
            };

            const updateSummary = function () {
                const labels = selectedLabels();

                if (!labels.length) {
                    summary.textContent = 'Select procedures';
                    return;
                }

                if (labels.length <= 2) {
                    summary.textContent = labels.join(', ');
                    return;
                }

                summary.textContent = labels.slice(0, 2).join(', ') + ' +' + (labels.length - 2) + ' more';
            };

            const updateOtherVisibility = function () {
                if (!otherWrap) {
                    return;
                }

                const otherChecked = checkboxes.some((checkbox) => checkbox.value === 'other' && checkbox.checked);
                otherWrap.style.display = otherChecked ? '' : 'none';
            };

            const closePanel = function () {
                panel.classList.add('hidden');
                toggleButton.setAttribute('aria-expanded', 'false');
            };

            const openPanel = function () {
                panel.classList.remove('hidden');
                toggleButton.setAttribute('aria-expanded', 'true');
                searchInput.focus();
            };

            const filterItems = function (query) {
                const normalized = query.trim().toLowerCase();

                items.forEach((item) => {
                    const text = item.textContent.toLowerCase();
                    item.style.display = text.includes(normalized) ? '' : 'none';
                });
            };

            toggleButton.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                if (panel.classList.contains('hidden')) {
                    openPanel();
                } else {
                    closePanel();
                }
            });

            checkboxes.forEach((checkbox) => {
                checkbox.addEventListener('change', function () {
                    updateSummary();
                    updateOtherVisibility();
                });
            });

            searchInput.addEventListener('input', function () {
                filterItems(searchInput.value);
            });

            document.addEventListener('click', function (event) {
                const target = event.target;

                if (!(target instanceof Element)) {
                    return;
                }

                if (!target.closest('[data-procedure-picker]')) {
                    closePanel();
                }
            });

            document.addEventListener('keydown', function (event) {
                if (event.key === 'Escape') {
                    closePanel();
                }
            });

            updateSummary();
            updateOtherVisibility();
        });
    </script>
@endsection
