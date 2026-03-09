@extends('layout.layout')

@php
    $title = 'Campaign Management';
    $subTitle = 'Send Deals & Information';
    $canSendEmailCampaign = auth()->user()?->hasModulePermission('campaign_management', 'send_email_campaign') ?? false;
    $canSendWhatsAppCampaign = auth()->user()?->hasModulePermission('campaign_management', 'send_whatsapp_campaign') ?? false;
    $canSendAnyCampaign = $canSendEmailCampaign || $canSendWhatsAppCampaign;
@endphp

@section('content')
    @if (session('campaign_status'))
        <div class="alert alert-success px-4 py-3 rounded-lg mb-6">
            {{ session('campaign_status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="alert alert-danger px-4 py-3 rounded-lg mb-6">
            {{ $errors->first() }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
        <div class="lg:col-span-8">
            <div class="card border-0">
                <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700 px-6 py-4">
                    <h6 class="mb-0 font-semibold text-lg">Campaign Message Center</h6>
                </div>
                <div class="card-body p-6">
                    <form method="POST" action="{{ route('emailCampaignSend') }}" class="space-y-4">
                        @csrf

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="form-label @error('channel') text-danger-600 @enderror">Send Via <span class="text-danger-600">*</span></label>
                                <select
                                    name="channel"
                                    id="campaign-channel"
                                    class="form-select rounded-lg @error('channel') border-danger-500 @enderror"
                                    required
                                    @disabled(!$canSendAnyCampaign)
                                >
                                    @if ($canSendEmailCampaign)
                                        <option value="email" {{ old('channel', 'email') === 'email' ? 'selected' : '' }}>Email</option>
                                    @endif
                                    @if ($canSendWhatsAppCampaign)
                                        <option value="whatsapp" {{ old('channel') === 'whatsapp' ? 'selected' : '' }}>WhatsApp (Twilio)</option>
                                    @endif
                                </select>
                                @error('channel')
                                    <p class="text-danger-600 text-xs mt-1 mb-0">{{ $message }}</p>
                                @enderror
                                @if (!$canSendAnyCampaign)
                                    <p class="text-danger-600 text-xs mt-1 mb-0">You do not have permission to send campaigns.</p>
                                @endif
                            </div>

                            <div>
                                <label class="form-label @error('audience') text-danger-600 @enderror">Audience Segment <span class="text-danger-600">*</span></label>
                                <select
                                    name="audience"
                                    class="form-select rounded-lg @error('audience') border-danger-500 @enderror"
                                    required
                                >
                                    @foreach ($audienceOptions as $audienceKey => $audienceLabel)
                                        <option value="{{ $audienceKey }}" {{ old('audience', 'all') === $audienceKey ? 'selected' : '' }}>
                                            {{ $audienceLabel }} ({{ number_format($audienceCounts[$audienceKey] ?? 0) }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('audience')
                                    <p class="text-danger-600 text-xs mt-1 mb-0">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div id="campaign-subject-group">
                            <label class="form-label @error('subject') text-danger-600 @enderror">Campaign Subject <span class="text-danger-600">*</span></label>
                            <input
                                type="text"
                                name="subject"
                                id="campaign-subject"
                                value="{{ old('subject') }}"
                                class="form-control rounded-lg @error('subject') border-danger-500 @enderror"
                                maxlength="160"
                                placeholder="Example: Limited Time Ramadan Deals"
                            >
                            @error('subject')
                                <p class="text-danger-600 text-xs mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label class="form-label @error('message') text-danger-600 @enderror">Campaign Message (Block Form) <span class="text-danger-600">*</span></label>
                            <textarea
                                name="message"
                                class="form-control rounded-lg @error('message') border-danger-500 @enderror"
                                rows="10"
                                maxlength="5000"
                                placeholder="Write your campaign message in block form..."
                                required
                            >{{ old('message') }}</textarea>
                            @error('message')
                                <p class="text-danger-600 text-xs mt-1 mb-0">{{ $message }}</p>
                            @enderror
                            <p class="text-xs text-secondary-light mt-2 mb-0">This block message will be sent to selected audience segment.</p>
                        </div>

                        <div id="manual-emails-group">
                            <label class="form-label @error('manual_emails') text-danger-600 @enderror">Add More Emails Manually (Optional)</label>
                            <textarea
                                name="manual_emails"
                                class="form-control rounded-lg @error('manual_emails') border-danger-500 @enderror"
                                rows="3"
                                placeholder="Enter email addresses separated by comma or new line"
                            >{{ old('manual_emails') }}</textarea>
                            @error('manual_emails')
                                <p class="text-danger-600 text-xs mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>

                        <div id="manual-numbers-group">
                            <label class="form-label @error('manual_numbers') text-danger-600 @enderror">Add More Numbers Manually (Optional)</label>
                            <textarea
                                name="manual_numbers"
                                class="form-control rounded-lg @error('manual_numbers') border-danger-500 @enderror"
                                rows="3"
                                placeholder="Enter WhatsApp numbers separated by comma or new line. Example: +923001234567"
                            >{{ old('manual_numbers') }}</textarea>
                            @error('manual_numbers')
                                <p class="text-danger-600 text-xs mt-1 mb-0">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button type="submit" class="btn btn-primary text-sm btn-sm px-4 py-2 rounded-lg" @disabled(!$canSendAnyCampaign)>
                                Send Campaign
                            </button>
                            <a href="{{ route('email') }}" class="btn btn-light text-sm btn-sm px-4 py-2 rounded-lg border border-neutral-200 dark:border-neutral-600">
                                Clear
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="lg:col-span-4">
            <div class="grid grid-cols-1 gap-4">
                <div class="card border border-neutral-200 dark:border-neutral-600 shadow-none rounded-lg">
                    <div class="card-body p-4">
                        <p class="text-sm text-secondary-light mb-1">Total Customers</p>
                        <h6 class="mb-0 dark:text-white">{{ number_format($totalCustomers) }}</h6>
                    </div>
                </div>
                <div class="card border border-neutral-200 dark:border-neutral-600 shadow-none rounded-lg">
                    <div class="card-body p-4">
                        <p class="text-sm text-secondary-light mb-1">Customers with Email</p>
                        <h6 class="mb-0 dark:text-white">{{ number_format($emailableCustomers) }}</h6>
                    </div>
                </div>
                <div class="card border border-neutral-200 dark:border-neutral-600 shadow-none rounded-lg">
                    <div class="card-body p-4">
                        <p class="text-sm text-secondary-light mb-1">Customers with WhatsApp Number</p>
                        <h6 class="mb-0 dark:text-white">{{ number_format($whatsAppCustomers) }}</h6>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 mt-6">
        <div class="card-header border-b border-neutral-200 dark:border-neutral-600 bg-white dark:bg-neutral-700 px-6 py-4">
            <h6 class="mb-0 font-semibold text-lg">Recent Customers (Campaign Audience Preview)</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table bordered-table mb-0">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Contact Channels</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentCustomers as $customer)
                            <tr>
                                <td>{{ $customer->full_name ?: 'Unnamed' }}</td>
                                <td>{{ $customer->email ?: 'No email' }}</td>
                                <td>{{ $customer->phone ?: 'No phone' }}</td>
                                <td>
                                    <div class="flex flex-wrap gap-2">
                                        @if (!empty($customer->email))
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-info-100 text-info-600">Email</span>
                                        @endif
                                        @if (!empty($customer->phone))
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-success-100 text-success-600">WhatsApp</span>
                                        @endif
                                        @if (empty($customer->email) && empty($customer->phone))
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-neutral-200 text-neutral-700">No Channel</span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center py-8 text-secondary-light">No customers found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const channelSelect = document.getElementById('campaign-channel');
            const subjectGroup = document.getElementById('campaign-subject-group');
            const subjectInput = document.getElementById('campaign-subject');
            const manualEmailsGroup = document.getElementById('manual-emails-group');
            const manualNumbersGroup = document.getElementById('manual-numbers-group');

            if (!channelSelect || !subjectGroup || !subjectInput || !manualEmailsGroup || !manualNumbersGroup) {
                return;
            }

            const syncChannelFields = () => {
                const channel = channelSelect.value;
                const isEmail = channel === 'email';

                subjectGroup.style.display = isEmail ? '' : 'none';
                manualEmailsGroup.style.display = isEmail ? '' : 'none';
                manualNumbersGroup.style.display = isEmail ? 'none' : '';
                subjectInput.required = isEmail;
            };

            channelSelect.addEventListener('change', syncChannelFields);
            syncChannelFields();
        })();
    </script>
@endsection
